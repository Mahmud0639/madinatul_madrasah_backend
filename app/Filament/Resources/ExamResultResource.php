<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Models\Department;
use App\Models\ExamGradingSetting;
use App\Models\ExamResult;
use App\Models\MadrasahClass;
use App\Models\Student;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'একাডেমিক ও পরীক্ষা';

    protected static ?string $navigationLabel = 'পরীক্ষার ফলাফল';

    protected static ?string $modelLabel = 'পরীক্ষার ফলাফল';

    protected static ?string $pluralModelLabel = 'পরীক্ষার ফলাফলসমূহ';

    protected static ?int $navigationSort = 1;

    /**
     * জামাত/ক্লাস এবং বিভাগের ওপর ভিত্তি করে ছাত্রদের কুয়েরি ফিল্টার
     */
    protected static function filteredStudentsQuery(?int $departmentId, ?string $classValue): Builder
    {
        $query = Student::query()->orderBy('name');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if (filled($classValue)) {
            $classModel = MadrasahClass::query()->find((int) $classValue);
            $possibleNames = collect([$classValue, $classModel?->name, (string) $classModel?->id])
                ->filter(fn ($v) => filled($v))
                ->unique()
                ->values()
                ->all();

            $query->whereIn('jamatu_class', $possibleNames);
        }

        return $query;
    }

    /**
     * জামাত/ক্লাসের বিষয়গুলো নিয়ে আসা
     *
     * @return Collection<int, Subject>
     */
    protected static function subjectsForClass(?string $classValue): Collection
    {
        if (! filled($classValue)) {
            return collect();
        }

        $classId = null;

        if (is_numeric($classValue)) {
            $classId = (int) $classValue;
        } else {
            $classId = MadrasahClass::query()
                ->where('name', $classValue)
                ->value('id');
        }

        if (! $classId) {
            return collect();
        }

        return Subject::query()
            ->where('madrasah_class_id', $classId)
            ->orderBy('id')
            ->get();
    }

    /**
     * পরীক্ষার নামের ড্রপডাউন অপশন (আগে ব্যবহৃত নাম + প্রচলিত পরীক্ষার নাম)
     *
     * @return array<string, string>
     */
    protected static function examNameOptions(): array
    {
        $existing = ExamResult::query()
            ->whereNotNull('exam_name')
            ->where('exam_name', '!=', '')
            ->distinct()
            ->orderBy('exam_name')
            ->pluck('exam_name', 'exam_name')
            ->toArray();

        $defaults = [
            '১ম সাময়িক পরীক্ষা' => '১ম সাময়িক পরীক্ষা',
            '২য় সাময়িক পরীক্ষা / অর্ধবার্ষিক' => '২য় সাময়িক পরীক্ষা / অর্ধবার্ষিক',
            'বার্ষিক পরীক্ষা' => 'বার্ষিক পরীক্ষা',
            'মাসিক মূল্যায়ন পরীক্ষা' => 'মাসিক মূল্যায়ন পরীক্ষা',
        ];

        return $existing + $defaults;
    }

    /**
     * প্রতিটি বিষয়ের সারির নম্বর, শতাংশ ও গ্রেড পুনরায় হিসাব করা
     */
    public static function recalculateRow(Get $get, Set $set): void
    {
        $setting = ExamGradingSetting::activeSetting();
        $includeOral = (bool) ($setting->include_oral ?? true);
        $statusInput = (string) ($get('result_status') ?? 'passed');

        if (! $includeOral) {
            $set('oral_marks', 0);
        }

        if ($statusInput === 'absent') {
            $set('total_marks', 0);
            $set('percentage', 0);
            $set('grade', 'অনুপস্থিত');
            $set('gpa', 0);
            return;
        }

        $written = (float) ($get('written_marks') ?? 0);
        $oral = $includeOral ? (float) ($get('oral_marks') ?? 0) : 0.0;
        $fullMarks = (float) ($get('full_marks') ?: 100);
        $passMarks = (float) ($get('pass_marks') ?: 33);

        $total = round($written + $oral, 2);
        $percentage = $fullMarks > 0 ? round(($total / $fullMarks) * 100, 2) : 0;

        [$grade, $gpa, $status] = $setting->evaluateByPercentage($total, $percentage, $passMarks);

        $set('total_marks', $total);
        $set('percentage', $percentage);
        $set('grade', $grade);
        $set('gpa', $gpa);
        $set('result_status', $status);
    }

    /**
     * রিপিটারের সব বিষয় থেকে সার্বিক ফলাফল (মোট নম্বর, পূর্ণমান, %, গড় GPA, গ্রেড/তাকদির) হিসাব করা
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function calculateOverallSummary(array $rows): array
    {
        $setting = ExamGradingSetting::activeSetting();
        $includeOral = (bool) ($setting->include_oral ?? true);
        $failOnAnySubject = (bool) ($setting->fail_on_any_subject ?? true);

        $subjectCount = count($rows);
        $obtainedTotal = 0.0;
        $fullMarksTotal = 0.0;
        $passMarksTotal = 0.0;
        $gpaSum = 0.0;
        $hasFailedSubject = false;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $status = (string) ($row['result_status'] ?? 'passed');
            $written = (float) ($row['written_marks'] ?? 0);
            $oral = $includeOral ? (float) ($row['oral_marks'] ?? 0) : 0.0;
            $full = (float) ($row['full_marks'] ?: 100);
            $pass = (float) ($row['pass_marks'] ?: 33);

            $fullMarksTotal += $full;
            $passMarksTotal += $pass;

            if ($status === 'absent') {
                $gpaSum += 0.0;
                $hasFailedSubject = true;
                continue;
            }

            $total = round($written + $oral, 2);
            $percentage = $full > 0 ? round(($total / $full) * 100, 2) : 0;

            [$grade, $gpa, $evaluatedStatus] = $setting->evaluateByPercentage($total, $percentage, $pass);

            $obtainedTotal += $total;
            $gpaSum += (float) $gpa;

            if ($evaluatedStatus === 'failed' || $status === 'failed') {
                $hasFailedSubject = true;
            }
        }

        $obtainedTotal = round($obtainedTotal, 2);
        $fullMarksTotal = round($fullMarksTotal, 2);
        $passMarksTotal = round($passMarksTotal, 2);
        $overallPercentage = $fullMarksTotal > 0 ? round(($obtainedTotal / $fullMarksTotal) * 100, 2) : 0.0;
        $averageGpa = $subjectCount > 0 ? round($gpaSum / $subjectCount, 2) : 0.0;

        [$overallGrade, , $evaluatedOverallStatus] = $setting->evaluateByPercentage(
            $obtainedTotal,
            $overallPercentage,
            $passMarksTotal
        );

        if ($subjectCount === 0) {
            return [
                'subject_count' => 0,
                'obtained_total' => 0,
                'full_marks_total' => 0,
                'overall_percentage' => 0,
                'average_gpa' => 0,
                'overall_grade' => '-',
                'overall_status' => 'অপেক্ষমাণ',
                'is_passed' => false,
            ];
        }

        if ($failOnAnySubject && $hasFailedSubject) {
            $failLabel = $setting->grading_mode === 'school' ? 'F' : 'রাসিব';

            return [
                'subject_count' => $subjectCount,
                'obtained_total' => $obtainedTotal,
                'full_marks_total' => $fullMarksTotal,
                'overall_percentage' => $overallPercentage,
                'average_gpa' => 0.00,
                'overall_grade' => $failLabel,
                'overall_status' => 'অনুত্তীর্ণ (এক বা একাধিক বিষয়ে ফেল)',
                'is_passed' => false,
            ];
        }

        $isPassed = $evaluatedOverallStatus === 'passed';

        return [
            'subject_count' => $subjectCount,
            'obtained_total' => $obtainedTotal,
            'full_marks_total' => $fullMarksTotal,
            'overall_percentage' => $overallPercentage,
            'average_gpa' => $averageGpa,
            'overall_grade' => $overallGrade,
            'overall_status' => $isPassed ? 'উত্তীর্ণ' : 'অনুত্তীর্ণ',
            'is_passed' => $isPassed,
        ];
    }

    /**
     * সিলেক্ট করা জামাতের সব বিষয় রিপিটারে অটো-লোড করা
     */
    public static function populateSubjectsIntoRepeater(Get $get, Set $set): void
    {
        $classValue = $get('madrasah_class_id');
        $subjects = self::subjectsForClass($classValue);

        if ($subjects->isEmpty()) {
            return;
        }

        $rows = $subjects->map(function (Subject $subject) {
            $title = $subject->code ? "{$subject->subject_name} ({$subject->code})" : $subject->subject_name;

            return [
                'subject_name' => $title,
                'written_marks' => 0,
                'oral_marks' => 0,
                'full_marks' => 100,
                'pass_marks' => 33,
                'total_marks' => 0,
                'percentage' => 0,
                'grade' => '-',
                'gpa' => 0,
                'result_status' => 'passed',
                'remarks' => null,
            ];
        })->values()->all();

        $set('subjects_entry', $rows);
    }

    public static function form(Form $form): Form
    {
        $setting = ExamGradingSetting::activeSetting();
        $includeOral = (bool) ($setting->include_oral ?? true);

        return $form
            ->schema([
                Forms\Components\Section::make('বিভাগ, ক্লাস, ছাত্র ও পরীক্ষার তথ্য')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('বিভাগ')
                            ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (Set $set) {
                                $set('madrasah_class_id', null);
                                $set('student_id', null);
                                $set('subjects_entry', []);
                            }),

                        Forms\Components\Select::make('madrasah_class_id')
                            ->label('ক্লাস / জামাত')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->options(function (Get $get) {
                                $departmentId = $get('department_id');

                                $query = MadrasahClass::query()->orderBy('name');
                                if ($departmentId) {
                                    $query->where('department_id', $departmentId);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('student_id', null);
                                self::populateSubjectsIntoRepeater($get, $set);
                            }),

                        Forms\Components\Select::make('student_id')
                            ->label('ছাত্রের নাম')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (Get $get) {
                                $departmentId = $get('department_id') ? (int) $get('department_id') : null;
                                $classValue = $get('madrasah_class_id');

                                return self::filteredStudentsQuery($departmentId, $classValue)
                                    ->get()
                                    ->mapWithKeys(function (Student $student) {
                                        $parts = [$student->name];
                                        if (filled($student->roll_number)) {
                                            $parts[] = "রোল: {$student->roll_number}";
                                        }
                                        if (filled($student->jamatu_class)) {
                                            $parts[] = $student->jamatu_class;
                                        }

                                        return [$student->id => implode(' — ', $parts)];
                                    })
                                    ->toArray();
                            }),

                        Forms\Components\Select::make('exam_name')
                            ->label('পরীক্ষার নাম')
                            ->options(fn () => self::examNameOptions())
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('new_exam_name')
                                    ->label('নতুন পরীক্ষার নাম')
                                    ->required()
                                    ->maxLength(150)
                                    ->placeholder('যেমন: ১ম সাময়িক পরীক্ষা ২০২৬'),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                return trim((string) $data['new_exam_name']);
                            }),

                        Forms\Components\Placeholder::make('active_rule_info')
                            ->label('বর্তমান গ্রেডিং ও মৌখিক নিয়ম')
                            ->columnSpan(2)
                            ->content(function () use ($setting, $includeOral) {
                                $oralText = $includeOral ? 'মৌখিক নম্বর যুক্ত হবে' : 'শুধু লিখিত নম্বর গণনা হবে';
                                return "{$setting->name} ({$oralText})";
                            }),
                    ]),

                Forms\Components\Section::make('বিষয়ভিত্তিক নম্বর এন্ট্রি (এক পেজে একাধিক বা সব বিষয়)')
                    ->description('ক্লাস সিলেক্ট করলে ওই ক্লাসের বিষয়গুলো নিচে চলে আসবে। এডিট পেজে একক বিষয় আপডেট করা যাবে।')
                    ->visible(fn (string $operation) => $operation === 'create')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('loadClassSubjects')
                                ->label('ক্লাসের সব বিষয় লোড করুন')
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn (Get $get, Set $set) => self::populateSubjectsIntoRepeater($get, $set)),
                        ]),

                        Forms\Components\Repeater::make('subjects_entry')
                            ->label('বিষয়সমূহের ফলাফল তালিকা')
                            ->required()
                            ->minItems(1)
                            ->dehydrated()
                            ->live()
                            ->defaultItems(1)
                            ->addActionLabel('+ আরেকটি বিষয় যুক্ত করুন')
                            ->reorderable()
                            ->collapsible()
                            ->schema([
                                Forms\Components\Select::make('subject_name')
                                    ->label('বিষয়')
                                    ->searchable()
                                    ->allowHtml(false)
                                    ->required()
                                    ->options(function (Get $get) {
                                        $classValue = $get('../../madrasah_class_id');
                                        $classSubjects = self::subjectsForClass($classValue);

                                        if ($classSubjects->isNotEmpty()) {
                                            return $classSubjects->mapWithKeys(function (Subject $s) {
                                                $label = $s->code ? "{$s->subject_name} ({$s->code})" : $s->subject_name;
                                                return [$label => $label];
                                            })->toArray();
                                        }

                                        return Subject::query()
                                            ->orderBy('subject_name')
                                            ->pluck('subject_name', 'subject_name')
                                            ->toArray();
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('custom_subject')
                                            ->label('নতুন/কাস্টম বিষয়ের নাম')
                                            ->required()
                                            ->maxLength(150),
                                    ])
                                    ->createOptionUsing(fn (array $data): string => trim((string) $data['custom_subject'])),

                                Forms\Components\TextInput::make('written_marks')
                                    ->label('লিখিত')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                                Forms\Components\TextInput::make('oral_marks')
                                    ->label('মৌখিক')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->visible($includeOral)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                                Forms\Components\TextInput::make('full_marks')
                                    ->label('পূর্ণমান')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                                Forms\Components\TextInput::make('pass_marks')
                                    ->label('পাস নম্বর')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(33)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                                Forms\Components\TextInput::make('total_marks')
                                    ->label('মোট')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('percentage')
                                    ->label('%')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('grade')
                                    ->label('গ্রেড / তাকদির')
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('gpa')
                                    ->label('GPA')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(),

                                Forms\Components\Select::make('result_status')
                                    ->label('অবস্থা')
                                    ->options([
                                        'passed' => 'উত্তীর্ণ',
                                        'failed' => 'অনুত্তীর্ণ',
                                        'absent' => 'অনুপস্থিত',
                                    ])
                                    ->default('passed')
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                                Forms\Components\TextInput::make('remarks')
                                    ->label('মন্তব্য')
                                    ->columnSpan(2)
                                    ->placeholder('ঐচ্ছিক মন্তব্য...'),
                            ])
                            ->columns($includeOral ? 6 : 5),
                    ]),

                Forms\Components\Section::make('সর্বমোট ফলাফল ও গ্রেড বক্স')
                    ->description('সব বিষয়ের নম্বর যুক্ত করার পর মোট নম্বরের মধ্যে প্রাপ্ত নম্বর এবং গ্রেডিং সিস্টেম অনুযায়ী সর্বোপরি গ্রেড নিচে দেখানো হচ্ছে।')
                    ->visible(fn (string $operation) => $operation === 'create')
                    ->schema([
                        Forms\Components\Placeholder::make('final_result_box')
                            ->label('')
                            ->content(function (Get $get) use ($setting) {
                                $rows = $get('subjects_entry') ?? [];
                                $summary = self::calculateOverallSummary(is_array($rows) ? $rows : []);

                                $accentColor = $summary['is_passed'] ? '#22c55e' : '#ef4444';
                                $boxBg = $summary['is_passed']
                                    ? 'linear-gradient(135deg, rgba(34,197,94,0.12), rgba(16,185,129,0.05))'
                                    : 'linear-gradient(135deg, rgba(239,68,68,0.12), rgba(220,38,38,0.05))';

                                return new HtmlString("
                                    <div style='width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;'>
                                        <div style='padding:18px 20px;border-radius:14px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.03);'>
                                            <div style='font-size:13px;opacity:0.75;'>মোট নম্বরের মধ্যে প্রাপ্ত নম্বর</div>
                                            <div style='font-size:26px;font-weight:800;margin-top:6px;line-height:1.2;'>
                                                {$summary['obtained_total']}
                                                <span style='font-size:15px;font-weight:600;opacity:0.75;'>/ {$summary['full_marks_total']}</span>
                                            </div>
                                            <div style='font-size:13px;margin-top:6px;opacity:0.8;'>
                                                মোট শতকরা: <strong>{$summary['overall_percentage']}%</strong> ({$summary['subject_count']} টি বিষয়)
                                            </div>
                                        </div>

                                        <div style='padding:18px 20px;border-radius:14px;border:1px solid {$accentColor};background:{$boxBg};'>
                                            <div style='font-size:13px;opacity:0.85;'>সর্বোপরি গ্রেড / তাকদির ({$setting->name})</div>
                                            <div style='font-size:26px;font-weight:800;margin-top:6px;line-height:1.2;color:{$accentColor};'>
                                                {$summary['overall_grade']}
                                            </div>
                                            <div style='font-size:13px;margin-top:6px;font-weight:600;'>
                                                অবস্থা: {$summary['overall_status']} · গড় GPA: {$summary['average_gpa']}
                                            </div>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

                Forms\Components\Section::make('একক বিষয়ের নম্বর ও গ্রেডিং (এডিট মোড)')
                    ->visible(fn (string $operation) => $operation === 'edit')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('subject_name')
                            ->label('বিষয়')
                            ->required()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('written_marks')
                            ->label('লিখিত নম্বর')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                        Forms\Components\TextInput::make('oral_marks')
                            ->label('মৌখিক নম্বর')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->visible($includeOral)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                        Forms\Components\TextInput::make('full_marks')
                            ->label('পূর্ণমান')
                            ->numeric()
                            ->minValue(1)
                            ->default(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                        Forms\Components\TextInput::make('pass_marks')
                            ->label('পাস নম্বর')
                            ->numeric()
                            ->minValue(0)
                            ->default(33)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                        Forms\Components\Select::make('result_status')
                            ->label('ফলাফলের অবস্থা')
                            ->options([
                                'passed' => 'উত্তীর্ণ',
                                'failed' => 'অনুত্তীর্ণ',
                                'absent' => 'অনুপস্থিত',
                            ])
                            ->default('passed')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateRow($get, $set)),

                        Forms\Components\TextInput::make('total_marks')
                            ->label('মোট প্রাপ্ত নম্বর')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('percentage')
                            ->label('শতকরা হার')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->suffix('%'),

                        Forms\Components\TextInput::make('grade')
                            ->label('গ্রেড / তাকদির')
                            ->readOnly()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('gpa')
                            ->label('GPA পয়েন্ট')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('remarks')
                            ->label('মন্তব্য')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $setting = ExamGradingSetting::activeSetting();
        $includeOral = (bool) ($setting->include_oral ?? true);

        $gradeFilterOptions = collect($setting->grade_ranges ?? [])
            ->pluck('label', 'label')
            ->filter(fn ($value, $key) => filled($key) && filled($value))
            ->whenEmpty(fn () => collect([
                'মুমতাজ' => 'মুমতাজ',
                'জায়্যিদ জিদ্দান' => 'জায়্যিদ জিদ্দান',
                'জায়্যিদ' => 'জায়্যিদ',
                'মাকবুল' => 'মাকবুল',
                'রাসিব' => 'রাসিব',
                'A+' => 'A+',
                'A' => 'A',
                'F' => 'F',
            ]))
            ->toArray();

        return $table
        ->groups([
                Tables\Grouping\Group::make('student.name')
                    ->label('ছাত্রের নাম অনুযায়ী')
                    ->collapsible(),
                Tables\Grouping\Group::make('exam_name')
                    ->label('পরীক্ষা অনুযায়ী')
                    ->collapsible(),
            ])
            ->defaultGroup('student.name')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('ছাত্রের নাম')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.jamatu_class')
                    ->label('ক্লাস / জামাত')
                    ->sortable(),

                Tables\Columns\TextColumn::make('exam_name')
                    ->label('পরীক্ষা')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_name')
                    ->label('বিষয়')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('written_marks')
                    ->label('লিখিত')
                    ->sortable(),

                Tables\Columns\TextColumn::make('oral_marks')
                    ->label('মৌখিক')
                    ->visible($includeOral)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_marks')
                    ->label('মোট')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('শতাংশ')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('grade')
                    ->label('গ্রেড / তাকদির')
                    ->badge()
                    ->color(function (?string $state) use ($setting): string {
                        if (! $state || $state === 'অনুপস্থিত') {
                            return 'gray';
                        }

                        $matched = collect($setting->grade_ranges ?? [])
                            ->first(fn ($row) => ($row['label'] ?? null) === $state);

                        if ($matched !== null) {
                            return ($matched['is_pass'] ?? true) ? 'success' : 'danger';
                        }

                        return in_array($state, ['F', 'রাসিব'], true) ? 'danger' : 'success';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('result_status')
                    ->label('অবস্থা')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'passed' => 'উত্তীর্ণ',
                        'failed' => 'অনুত্তীর্ণ',
                        'absent' => 'অনুপস্থিত',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        'absent' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_name')
                    ->label('পরীক্ষা অনুযায়ী')
                    ->options(fn () => ExamResult::query()->whereNotNull('exam_name')->distinct()->pluck('exam_name', 'exam_name')->toArray()),

                Tables\Filters\SelectFilter::make('grade')
                    ->label('গ্রেড অনুযায়ী')
                    ->options($gradeFilterOptions),

                Tables\Filters\SelectFilter::make('result_status')
                    ->label('ফলাফল অবস্থা')
                    ->options([
                        'passed' => 'উত্তীর্ণ',
                        'failed' => 'অনুত্তীর্ণ',
                        'absent' => 'অনুপস্থিত',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'create' => Pages\CreateExamResult::route('/create'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }
}