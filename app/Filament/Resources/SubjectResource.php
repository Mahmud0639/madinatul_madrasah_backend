<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?string $navigationLabel = 'বিষয় ও কিতাব';
    protected static ?string $pluralModelLabel = 'বিষয়সমূহ';
    protected static ?string $modelLabel = 'বিষয়';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('madrasah_class_id')
                    ->label('ক্লাস / জামাত সিলেক্ট করুন')
                    ->relationship('madrasahClass', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('subject_name')
                    ->label('বিষয় বা কিতাবের নাম')
                    ->options(function () {
                        return Subject::query()
                            ->distinct()
                            ->pluck('subject_name', 'subject_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->required()
                    ->live() // লাইভ রিয়্যাক্টিভিটি অন করা হলো যাতে সিলেক্ট করার সাথে সাথেই কাজ করে
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $subjectName = trim($state);
                            
                            // বাংলা ও সাধারণ বিষয়গুলোর জন্য নির্দিষ্ট শর্টকোড ম্যাপিং
                            $subjectCodes = [
                                'বাংলা' => 'BAN',
                                'ইংরেজি' => 'ENG',
                                'গণিত' => 'MAT',
                                'বিজ্ঞান' => 'SCI',
                                'আইসিটি' => 'ICT',
                                'আরবি' => 'ARB',
                                'ফিকহ' => 'FIQ',
                                'আকাইদ' => 'AQI',
                                'সবক' => 'SBK',
                                'সবকিরা' => 'SKR',
                                'মিজান' => 'MIZ',
                                'নাহু' => 'NAH',
                            ];

                            $prefix = $subjectCodes[$subjectName] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subjectName), 0, 3));
                            if (empty($prefix)) {
                                $prefix = 'SUB';
                            }

                            // ডাটাবেজ চেক করে শতভাগ ইউনিক ও ডুপ্লিকেট-মুক্ত কোড জেনারেট করা
                            do {
                                $code = $prefix . '-' . rand(100, 999);
                            } while (Subject::where('code', $code)->exists());

                            // কোড ফিল্ডে স্বয়ংক্রিয়ভাবে মান বসিয়ে দেওয়া
                            $set('code', $code);
                        }
                    })
                    ->createOptionForm([
                        Forms\Components\TextInput::make('subject_name')
                            ->label('নতুন বিষয় বা কিতাবের নাম')
                            ->placeholder('যেমন: মিজান, নাহু-মীর, গণিত')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->unique(modifyRuleUsing: function ($rule, callable $get) {
                        return $rule->where('madrasah_class_id', $get('madrasah_class_id'));
                    }, ignoreRecord: true),

                Forms\Components\TextInput::make('code')
                    ->label('সাবজেক্ট কোড')
                    ->placeholder('যেমন: MAT-101')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generateCode')
                            ->icon('heroicon-m-plus')
                            ->tooltip('ক্লিক করে নতুন কোড তৈরি করুন')
                            ->action(function (callable $get, callable $set) {
                                $subjectName = trim($get('subject_name') ?? '');
                                
                                $subjectCodes = [
                                    'বাংলা' => 'BAN',
                                    'ইংরেজি' => 'ENG',
                                    'গণিত' => 'MAT',
                                    'বিজ্ঞান' => 'SCI',
                                    'আইসিটি' => 'ICT',
                                    'আরবি' => 'ARB',
                                    'ফিকহ' => 'FIQ',
                                    'আকাইদ' => 'AQI',
                                    'সবক' => 'SBK',
                                    'সবকিরা' => 'SKR',
                                    'মিজান' => 'MIZ',
                                    'নাহু' => 'NAH',
                                ];

                                $prefix = $subjectCodes[$subjectName] ?? 'SUB';

                                do {
                                    $code = $prefix . '-' . rand(100, 999);
                                } while (Subject::where('code', $code)->exists());

                                $set('code', $code);
                            })
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('madrasahClass.name')
                    ->label('ক্লাস / জামাত')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('বিষয় / কিতাবের নাম')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('কোড')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('তৈরির তারিখ')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('madrasah_class_id')
                    ->label('ক্লাস অনুযায়ী ফিল্টার করুন')
                    ->relationship('madrasahClass', 'name'),
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
            'index' => Pages\ManageSubjects::route('/'),
        ];
    }
}