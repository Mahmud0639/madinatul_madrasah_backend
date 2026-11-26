<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MadrasahClassResource\Pages;
use App\Models\MadrasahClass;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MadrasahClassResource extends Resource
{
    protected static ?string $model = MadrasahClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'ক্লাস ব্যবস্থাপনা';
    protected static ?string $pluralModelLabel = 'ক্লাসসমূহ';
    protected static ?string $navigationGroup = 'একাডেমিক ও পাঠদান';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ১. বিভাগ সিলেক্ট করুন (ড্রপডাউন + প্লাস আইকনসহ নতুন বিভাগ ইনলাইন তৈরির অপশন)
                Forms\Components\Select::make('department_id')
                    ->label('বিভাগ সিলেক্ট করুন')
                    ->relationship('department', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live() // বিভাগ পরিবর্তন করলে ক্লাসের ড্রপডাউন সাথে সাথে আপডেট হবে
                    ->afterStateUpdated(fn (callable $set) => $set('name', null))
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('নতুন বিভাগের নাম (যেমন: হিফজ বিভাগ)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('বিভাগ কোড (যেমন: HIFZ)')
                            ->maxLength(255),
                    ]),

                // ২. ক্লাস / জামাতের নাম (বিভাগের ওপর ভিত্তি করে ড্রপডাউন বা প্লাস আইকন দিয়ে নতুন ক্লাস যোগ করার অপশন)
                Forms\Components\Select::make('name')
                    ->label('ক্লাস / জামাতের নাম')
                    ->options(function (callable $get) {
                        $departmentId = $get('department_id');
                        if (!$departmentId) {
                            return MadrasahClass::pluck('name', 'name')->toArray();
                        }
                        // শুধুমাত্র সিলেক্ট করা বিভাগের আন্ডারে থাকা ক্লাসগুলো দেখাবে
                        return MadrasahClass::where('department_id', $departmentId)
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('নতুন ক্লাসের নাম (যেমন: নাজেরা প্রথম জামাত)')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data, callable $get) {
                        $departmentId = $get('department_id');
                        // ইনলাইন নতুন ক্লাস তৈরি করে নির্দিষ্ট বিভাগের সাথে সেভ করবে
                        $class = MadrasahClass::create([
                            'name' => $data['name'],
                            'department_id' => $departmentId,
                        ]);
                        return $class->name;
                    })
                    ->columnSpanFull(),

                // ৩. বিষয় বা কিতাবসমূহ (রিপিটার)
                Forms\Components\Repeater::make('subjects')
                    ->relationship('subjects')
                    ->label('এই ক্লাসের বিষয় বা কিতাবসমূহ যুক্ত করুন')
                    ->schema([
                        Forms\Components\Select::make('subject_name')
                            ->label('বিষয় বা কিতাবের নাম')
                            ->options(function (callable $get) {
                                $className = $get('../../name');
                                $departmentId = $get('../../department_id');
                                
                                $query = Subject::query();
                                if ($className) {
                                    $classRecord = MadrasahClass::where('name', $className)
                                        ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
                                        ->first();
                                    if ($classRecord) {
                                        $query->where('madrasah_class_id', $classRecord->id);
                                    }
                                }
                                
                                $subjects = $query->pluck('subject_name', 'subject_name')->toArray();
                                if (empty($subjects)) {
                                    $subjects = Subject::pluck('subject_name', 'subject_name')->toArray();
                                }
                                return $subjects;
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $existingSubject = Subject::where('subject_name', $state)->first();
                                    if ($existingSubject) {
                                        $set('code', $existingSubject->code);
                                    } else {
                                        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $state), 0, 3));
                                        $set('code', ($prefix ?: 'SUB') . '-' . rand(10, 99));
                                    }
                                }
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('subject_name')
                                    ->label('নতুন বিষয় বা কিতাবের নাম')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->label('সাবজেক্ট কোড (ইউনিক)')
                                    ->required()
                                    ->unique('subjects', 'code')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\TextInput::make('code')
                            ->label('সাবজেক্ট কোড (ইউনিক)')
                            ->required()
                            ->placeholder('যেমন: NZR-01')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generateCode')
                                    ->icon('heroicon-m-plus')
                                    ->tooltip('ক্লিক করে কোড জেনারেট করুন')
                                    ->action(function (callable $get, callable $set) {
                                        $subjectName = trim($get('subject_name') ?? '');
                                        $prefix = 'SUB';
                                        if (!empty($subjectName)) {
                                            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subjectName), 0, 3));
                                        }
                                        $code = $prefix . '-' . rand(100, 999);
                                        $set('code', $code);
                                    })
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->addActionLabel('＋ আরেকটি বিষয় যোগ করুন')
                    ->defaultItems(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('ক্লাসের নাম')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('বিভাগ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subjects_count')
                    ->label('মোট বিষয়')
                    ->counts('subjects'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('তৈরির তারিখ')
                    ->date('d M, Y')
                    ->sortable(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMadrasahClasses::route('/index'),
            'create' => Pages\CreateMadrasahClass::route('/create'),
            'edit' => Pages\EditMadrasahClass::route('/{record}/edit'),
        ];
    }
}