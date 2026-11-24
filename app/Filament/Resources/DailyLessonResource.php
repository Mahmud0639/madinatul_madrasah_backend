<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyLessonResource\Pages;
use App\Models\DailyLesson;
use App\Models\Department;
use App\Models\LessonStudentStatus;
use App\Models\LessonTemplate;
use App\Models\MadrasahClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyLessonResource extends Resource
{
    protected static ?string $model = DailyLesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'দৈনিক পড়া (Lesson)';
    protected static ?string $pluralModelLabel = 'দৈনিক পড়াসমূহ';
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('বিভাগ, ক্লাস, বিষয় ও সময়সূচি')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('বিভাগ')
                            ->options(fn () => Department::pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('madrasah_class_id', null);
                                $set('subject_id', null);
                                $set('subject_name', null);
                                $set('target_student_ids', []);
                            }),

                        Forms\Components\Select::make('madrasah_class_id')
                            ->label('ক্লাস / জামাত')
                            ->options(function (callable $get) {
                                $departmentId = $get('department_id');
                                $query = MadrasahClass::query();

                                if ($departmentId) {
                                    $query->where('department_id', $departmentId);
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('subject_id', null);
                                $set('subject_name', null);
                                $set('target_student_ids', []);
                            })
                            ->required(),

                        Forms\Components\Select::make('subject_id')
                            ->label('বিষয় / কিতাব')
                            ->options(function (callable $get) {
                                $classId = $get('madrasah_class_id');
                                if (!$classId) {
                                    return Subject::pluck('subject_name', 'id')->toArray();
                                }
                                return Subject::where('madrasah_class_id', $classId)
                                    ->pluck('subject_name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $subject = Subject::find($state);
                                    $set('subject_name', $subject?->subject_name);
                                }
                            }),

                        Forms\Components\TextInput::make('subject_name')
                            ->label('বিষয় / কিতাবের নাম (সরাসরি লিখলে)')
                            ->placeholder('যেমন: কায়দা, আমপারা, গণিত')
                            ->maxLength(255),

                        Forms\Components\Select::make('teacher_id')
                            ->label('উস্তাদ / শিক্ষক')
                            ->options(fn () => User::where('role', 'teacher')->pluck('name', 'id')->toArray())
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('lesson_date')
                            ->label('পড়া দেওয়ার তারিখ')
                            ->default(now()->timezone('Asia/Dhaka')->toDateString())
                            ->native(false)
                            ->required(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('শুনানো / জমা দেওয়ার তারিখ')
                            ->default(now()->timezone('Asia/Dhaka')->addDay()->toDateString())
                            ->native(false),
                    ])->columns(3),

                Forms\Components\Section::make('পড়া লেখা ও কুইক টুলস')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('template_picker')
                                    ->label('কুইক টেমপ্লেট ব্যবহার করুন')
                                    ->options(fn () => LessonTemplate::where('is_active', true)->pluck('title', 'id')->toArray())
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (!$state) {
                                            return;
                                        }
                                        $tpl = LessonTemplate::find($state);
                                        if ($tpl) {
                                            $set('lesson_type', $tpl->lesson_type);
                                            $set('title', $tpl->title);
                                            $set('description', $tpl->body_template);
                                        }
                                    }),

                                Forms\Components\Select::make('lesson_type')
                                    ->label('পড়ার ধরন')
                                    ->options(DailyLesson::typeLabels())
                                    ->default('sabak')
                                    ->required(),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('copyPreviousLesson')
                                        ->label('আগের পড়া কপি করুন')
                                        ->icon('heroicon-m-clipboard-document')
                                        ->color('gray')
                                        ->action(function (callable $get, callable $set) {
                                            $classId = $get('madrasah_class_id');
                                            if (!$classId) {
                                                Notification::make()
                                                    ->warning()
                                                    ->title('আগে ক্লাস/জামাত সিলেক্ট করুন')
                                                    ->send();
                                                return;
                                            }

                                            $query = DailyLesson::where('madrasah_class_id', $classId);
                                            if ($get('subject_id')) {
                                                $query->where('subject_id', $get('subject_id'));
                                            } elseif ($get('subject_name')) {
                                                $query->where('subject_name', $get('subject_name'));
                                            }

                                            $prev = $query->latest('lesson_date')->latest('id')->first();
                                            if (!$prev) {
                                                Notification::make()
                                                    ->info()
                                                    ->title('আগের কোনো পড়া পাওয়া যায়নি')
                                                    ->send();
                                                return;
                                            }

                                            $set('lesson_type', $prev->lesson_type);
                                            $set('title', $prev->title);
                                            $set('description', $prev->description);

                                            Notification::make()
                                                ->success()
                                                ->title('আগের পড়া কপি করা হয়েছে')
                                                ->send();
                                        }),
                                ])->verticallyAlignEnd(),
                            ])->columnSpanFull(),

                        Forms\Components\TextInput::make('title')
                            ->label('পড়ার শিরোনাম')
                            ->placeholder('যেমন: সূরা মুলক ১–৫ আয়াত বা অনুশীলনী ৩.২')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('বিস্তারিত পড়া (শিক্ষকের নির্দেশনা)')
                            ->placeholder('কোন পৃষ্ঠা, আয়াত, কিতাব বা অঙ্ক কীভাবে পড়তে/লিখতে হবে তা বিস্তারিত লিখুন...')
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('কার জন্য পড়া (টার্গেটিং)')
                    ->schema([
                        Forms\Components\Radio::make('target_type')
                            ->label('টার্গেট নির্বাচন')
                            ->options([
                                'all_class' => 'পুরো ক্লাস / জামাতের সব ছাত্র',
                                'specific_students' => 'নির্দিষ্ট কয়েকজন ছাত্র (বিশেষ / রিভিশন পড়া)',
                            ])
                            ->default('all_class')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('target_student_ids')
                            ->label('ছাত্র নির্বাচন করুন')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('target_type') === 'specific_students')
                            ->required(fn (callable $get) => $get('target_type') === 'specific_students')
                            ->options(function (callable $get) {
                                $classId = $get('madrasah_class_id');
                                $departmentId = $get('department_id');

                                $query = Student::query();

                                if ($departmentId) {
                                    $query->where('department_id', $departmentId);
                                }

                                if ($classId) {
                                    $className = MadrasahClass::find($classId)?->name;
                                    if ($className) {
                                        $query->where('jamatu_class', $className);
                                    }
                                }

                                return $query->pluck('name', 'id')->toArray();
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('lesson_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('lesson_date')
                    ->label('তারিখ')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('madrasahClass.name')
                    ->label('ক্লাস / জামাত')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_name')
                    ->label('বিষয় / কিতাব')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('পড়ার শিরোনাম')
                    ->searchable()
                    ->weight('bold')
                    ->limit(35),

                Tables\Columns\TextColumn::make('target_type')
                    ->label('টার্গেট')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'specific_students' ? 'নির্দিষ্ট ছাত্র' : 'পুরো ক্লাস')
                    ->color(fn ($state) => $state === 'specific_students' ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('completed_count')
                    ->label('সম্পন্ন (গার্ডিয়ান)')
                    ->getStateUsing(function (DailyLesson $record) {
                        $total = $record->studentStatuses()->count();
                        $done = $record->studentStatuses()->whereNotNull('completed_at')->count();
                        return "{$done} / {$total}";
                    }),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('উস্তাদ')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('বিভাগ অনুযায়ী ফিল্টার')
                    ->options(fn () => Department::pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('madrasah_class_id')
                    ->label('ক্লাস অনুযায়ী ফিল্টার')
                    ->relationship('madrasahClass', 'name'),

                Tables\Filters\SelectFilter::make('lesson_type')
                    ->label('ধরন')
                    ->options(DailyLesson::typeLabels()),
            ])
            ->actions([
                Tables\Actions\Action::make('evaluate')
                    ->label('মূল্যায়ন')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->mountUsing(function (Forms\Form $form, DailyLesson $record) {
                        $items = $record->studentStatuses()->with('student')->get()->map(function ($st) {
                            return [
                                'status_id' => $st->id,
                                'student_name' => $st->student->name ?? ('ছাত্র #' . $st->student_id),
                                'guardian_done' => $st->completed_at ? 'হ্যাঁ (' . $st->completed_at->format('h:i A') . ')' : 'না',
                                'guardian_note' => $st->guardian_note ?? '-',
                                'teacher_status' => $st->teacher_status,
                                'teacher_remark' => $st->teacher_remark,
                            ];
                        })->toArray();

                        $form->fill(['evaluations' => $items]);
                    })
                    ->form([
                        Forms\Components\Repeater::make('evaluations')
                            ->label('ছাত্রদের পড়া মূল্যায়ন')
                            ->addable(false)
                            ->deletable(false)
                            ->schema([
                                Forms\Components\Hidden::make('status_id'),
                                Forms\Components\Placeholder::make('student_info')
                                    ->label('ছাত্র ও গার্ডিয়ান আপডেট')
                                    ->content(fn (callable $get) => ($get('student_name') ?? '') . ' | গার্ডিয়ান সম্পন্ন: ' . ($get('guardian_done') ?? 'না') . ' | নোট: ' . ($get('guardian_note') ?? '-')),

                                Forms\Components\Select::make('teacher_status')
                                    ->label('শিক্ষকের মূল্যায়ন')
                                    ->options(LessonStudentStatus::teacherStatusLabels())
                                    ->required(),

                                Forms\Components\TextInput::make('teacher_remark')
                                    ->label('শিক্ষকের মন্তব্য (ঐচ্ছিক)')
                                    ->placeholder('যেমন: চমৎকার মুখস্থ হয়েছে বা কাল আবার ধরবে'),
                            ])->columns(2),
                    ])
                    ->action(function (array $data) {
                        foreach ($data['evaluations'] ?? [] as $row) {
                            LessonStudentStatus::where('id', $row['status_id'])->update([
                                'teacher_status' => $row['teacher_status'],
                                'teacher_remark' => $row['teacher_remark'] ?? null,
                                'verified_at' => now()->timezone('Asia/Dhaka'),
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title('শিক্ষকের মূল্যায়ন সংরক্ষণ করা হয়েছে')
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyLessons::route('/index'),
            'create' => Pages\CreateDailyLesson::route('/create'),
            'edit' => Pages\EditDailyLesson::route('/{record}/edit'),
        ];
    }
}