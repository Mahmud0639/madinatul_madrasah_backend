<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'হাজিরা ও ছুটি';

    protected static ?string $navigationLabel = 'ছুটি ক্যালেন্ডার';

    protected static ?string $modelLabel = 'ছুটির দিন';

    protected static ?string $pluralModelLabel = 'ছুটি ক্যালেন্ডার';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ছুটির তথ্য')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('ছুটির কারণ / শিরোনাম')
                            ->placeholder('যেমন: শবে বরাত / বার্ষিক পরীক্ষা ছুটি')
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('ছুটির ধরন')
                            ->options([
                                'weekly' => 'সাপ্তাহিক ছুটি',
                                'public' => 'সরকারি / জাতীয় ছুটি',
                                'madrasah' => 'মাদ্রাসার বিশেষ ছুটি',
                            ])
                            ->default('madrasah')
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('ছুটি শুরুর তারিখ')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('ছুটি শেষের তারিখ')
                            ->afterOrEqual('start_date')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('সক্রিয় ছুটি')
                            ->default(true),

                        Forms\Components\Textarea::make('note')
                            ->label('মন্তব্য / নোট')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('ছুটির কারণ')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('শুরু')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('শেষ')
                    ->date('d M, Y'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('ধরন')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'weekly' => 'সাপ্তাহিক ছুটি',
                        'public' => 'সরকারি ছুটি',
                        default => 'মাদ্রাসার ছুটি',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('সক্রিয়')
                    ->boolean(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('ছুটির ধরন')
                    ->options([
                        'weekly' => 'সাপ্তাহিক ছুটি',
                        'public' => 'সরকারি ছুটি',
                        'madrasah' => 'মাদ্রাসার ছুটি',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('সক্রিয় অবস্থা'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}