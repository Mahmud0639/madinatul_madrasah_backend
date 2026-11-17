<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?string $navigationLabel = 'বিভাগসমূহ (Departments)';
    protected static ?string $pluralModelLabel = 'বিভাগসমূহ';
    protected static ?string $modelLabel = 'বিভাগ';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('name')
                    ->label('বিভাগের নাম (যেমন: হিফজ বিভাগ, নূরানি বিভাগ)')
                    ->options(function () {
                        // আগে থেকে সেভ করা বিভাগগুলো + ডিফল্ট কমন বিভাগগুলো দেখাবে
                        $defaults = [
                            'নূরানি বিভাগ' => 'নূরানি বিভাগ',
                            'নাজেরা বিভাগ' => 'নাজেরা বিভাগ',
                            'হিফজ বিভাগ' => 'হিফজ বিভাগ',
                            'কিতাব বিভাগ' => 'কিতাব বিভাগ',
                            'মক্তব বিভাগ' => 'মক্তব বিভাগ',
                        ];

                        $saved = Department::query()
                            ->pluck('name', 'name')
                            ->toArray();

                        return array_merge($defaults, $saved);
                    })
                    ->searchable()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $deptCodes = [
                                'নূরানি বিভাগ' => 'NNR',
                                'নাজেরা বিভাগ' => 'NZR',
                                'হিফজ বিভাগ' => 'HIFZ',
                                'কিতাব বিভাগ' => 'KTB',
                                'মক্তব বিভাগ' => 'MKT',
                            ];

                            $prefix = $deptCodes[trim($state)] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $state), 0, 4));
                            if (empty($prefix)) {
                                $prefix = 'DEPT';
                            }

                            $set('code', $prefix);
                        }
                    })
                    // পাশে প্লাস (+) আইকন যুক্ত করে হুজুরদের জন্য ইনলাইন নতুন বিভাগ যোগ করার সুবিধা:
                    ->createOptionForm([
                        Forms\Components\TextInput::make('new_department')
                            ->label('নতুন বিভাগের নাম লিখুন')
                            ->placeholder('যেমন: তাখাসসুস বিভাগ, ইফতা বিভাগ')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data): string {
                        return $data['new_department'];
                    }),

                Forms\Components\TextInput::make('code')
                    ->label('বিভাগ কোড (যেমন: HIFZ, NNR)')
                    ->placeholder('অটোমেটিক জেনারেট হবে অথবা নিজে লিখুন')
                    ->maxLength(255)
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generateDeptCode')
                            ->icon('heroicon-m-plus')
                            ->tooltip('ক্লিক করে ইউনিক কোড তৈরি করুন')
                            ->action(function (callable $get, callable $set) {
                                $name = trim($get('name') ?? '');
                                $deptCodes = [
                                    'নূরানি বিভাগ' => 'NNR',
                                    'নাজেরা বিভাগ' => 'NZR',
                                    'হিফজ বিভাগ' => 'HIFZ',
                                    'কিতাব বিভাগ' => 'KTB',
                                    'মক্তব বিভাগ' => 'MKT',
                                ];
                                $prefix = $deptCodes[$name] ?? 'DEPT';

                                do {
                                    $code = $prefix . '-' . rand(10, 99);
                                } while (Department::where('code', $code)->exists());

                                $set('code', $code);
                            })
                    ),

                Forms\Components\Textarea::make('description')
                    ->label('বিবরণ (ঐচ্ছিক)')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('বিভাগের নাম')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDepartments::route('/'),
        ];
    }
}