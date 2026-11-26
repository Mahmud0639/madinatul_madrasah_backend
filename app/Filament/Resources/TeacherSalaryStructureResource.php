<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherSalaryStructureResource\Pages;
use App\Models\TeacherSalaryStructure;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherSalaryStructureResource extends Resource
{
    protected static ?string $model = TeacherSalaryStructure::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'হিসাব ও ফাইন্যান্স';

    protected static ?string $navigationLabel = 'শিক্ষক বেতন কাঠামো';

    protected static ?string $modelLabel = 'বেতন কাঠামো';

    protected static ?string $pluralModelLabel = 'শিক্ষক বেতন কাঠামো';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('বেতন নির্ধারণ')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('শিক্ষক')
                            ->options(fn () => static::getTeacherOptions())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('effective_from')
                            ->label('কার্যকর হওয়ার তারিখ')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('basic_salary')
                            ->label('মূল বেতন (৳)')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\TextInput::make('house_rent')
                            ->label('বাড়ি ভাড়া / ভাতা (৳)')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('other_allowance')
                            ->label('অন্যান্য ভাতা (৳)')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->label('সক্রিয় কাঠামো')
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
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('শিক্ষক')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('basic_salary')
                    ->label('মূল বেতন')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('house_rent')
                    ->label('ভাতা')
                    ->money('BDT'),

                Tables\Columns\TextColumn::make('effective_from')
                    ->label('কার্যকর তারিখ')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('সক্রিয়')
                    ->boolean(),
            ])
            ->filters([
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
            'index' => Pages\ListTeacherSalaryStructures::route('/'),
            'create' => Pages\CreateTeacherSalaryStructure::route('/create'),
            'edit' => Pages\EditTeacherSalaryStructure::route('/{record}/edit'),
        ];
    }

    protected static function getTeacherOptions(): array
    {
        return User::query()
            ->where('role', 'teacher')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}