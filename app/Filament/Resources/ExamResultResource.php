<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Models\ExamResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'একাডেমিক ও পরীক্ষা';

    protected static ?string $navigationLabel = 'পরীক্ষার ফলাফল';

    protected static ?string $modelLabel = 'পরীক্ষার ফলাফল';

    protected static ?string $pluralModelLabel = 'পরীক্ষার ফলাফলসমূহ';

    protected static ?int $navigationSort = 1;

    public static function recalculateMarks(Get $get, Set $set): void
    {
        $written = (float) ($get('written_marks') ?? 0);
        $oral = (float) ($get('oral_marks') ?? 0);
        $fullMarks = (float) ($get('full_marks') ?: 100);
        $passMarks = (float) ($get('pass_marks') ?: 33);

        $total = round($written + $oral, 2);
        $percentage = $fullMarks > 0 ? round(($total / $fullMarks) * 100, 2) : 0;
        [$grade, $gpa, $status] = ExamResult::evaluateGrade($total, $percentage, $passMarks);

        $set('total_marks', $total);
        $set('percentage', $percentage);
        $set('grade', $grade);
        $set('gpa', $gpa);
        $set('result_status', $status);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ছাত্র ও পরীক্ষার তথ্য')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('student_id')
    ->label('ছাত্রের নাম')
    ->searchable()
    ->preload()
    ->required()
    ->options(function () {
        return \App\Models\Student::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($student) {
                $parts = [$student->name];

                if (!empty($student->jamatu_class)) {
                    $parts[] = $student->jamatu_class;
                }

                if (!empty($student->roll_number)) {
                    $parts[] = "রোল: {$student->roll_number}";
                }

                return [$student->id => implode(' — ', $parts)];
            })
            ->toArray();
    }),

                        Forms\Components\TextInput::make('exam_name')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('যেমন: প্রথম সাময়িক পরীক্ষা ২০২৬')
                            ->label('পরীক্ষার নাম'),

                        Forms\Components\TextInput::make('subject_name')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('যেমন: কুরআন মাজীদ / হিফজ / আরবি')
                            ->label('বিষয়'),

                        Forms\Components\Select::make('result_status')
                            ->options([
                                'passed' => 'উত্তীর্ণ (Passed)',
                                'failed' => 'অনুত্তীর্ণ (Failed)',
                                'absent' => 'অনুপস্থিত (Absent)',
                            ])
                            ->default('passed')
                            ->required()
                            ->label('ফলাফলের অবস্থা'),
                    ]),

                Forms\Components\Section::make('নম্বর ও স্বয়ংক্রিয় গ্রেডিং')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('written_marks')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateMarks($get, $set))
                            ->label('লিখিত নম্বর'),

                        Forms\Components\TextInput::make('oral_marks')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateMarks($get, $set))
                            ->label('মৌখিক নম্বর'),

                        Forms\Components\TextInput::make('full_marks')
                            ->numeric()
                            ->minValue(1)
                            ->default(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateMarks($get, $set))
                            ->label('পূর্ণমান'),

                        Forms\Components\TextInput::make('pass_marks')
                            ->numeric()
                            ->minValue(0)
                            ->default(33)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateMarks($get, $set))
                            ->label('পাস নম্বর'),

                        Forms\Components\TextInput::make('total_marks')
                            ->numeric()
                            ->required()
                            ->readOnly()
                            ->dehydrated()
                            ->label('মোট প্রাপ্ত নম্বর'),

                        Forms\Components\TextInput::make('percentage')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->suffix('%')
                            ->label('শতকরা হার'),

                        Forms\Components\TextInput::make('grade')
                            ->readOnly()
                            ->dehydrated()
                            ->label('গ্রেড'),

                        Forms\Components\TextInput::make('gpa')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->label('GPA পয়েন্ট'),
                    ]),

                Forms\Components\Section::make('শিক্ষকের মন্তব্য')
                    ->schema([
                        Forms\Components\Textarea::make('remarks')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('উস্তাদের মূল্যায়ন বা পরামর্শ লিখুন...')
                            ->label('মন্তব্য'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('ছাত্রের নাম')
                    ->searchable()
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
                    ->label('গ্রেড')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A+', 'A' => 'success',
                        'A-', 'B' => 'info',
                        'C', 'D' => 'warning',
                        'F' => 'danger',
                        default => 'gray',
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
                    ->options([
                        'A+' => 'A+',
                        'A' => 'A',
                        'A-' => 'A-',
                        'B' => 'B',
                        'C' => 'C',
                        'D' => 'D',
                        'F' => 'F',
                    ]),

                Tables\Filters\SelectFilter::make('result_status')
                    ->label('ফলাফল অবস্থা')
                    ->options([
                        'passed' => 'উত্তীর্ণ',
                        'failed' => 'অনুত্তীর্ণ',
                        'absent' => 'অনুপস্থিত',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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