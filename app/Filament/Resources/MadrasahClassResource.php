<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MadrasahClassResource\Pages;
use App\Models\MadrasahClass;
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
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('ক্লাস বা জামাতের নাম')
                    ->placeholder('যেমন: মিশকাত জামাত')
                    ->unique(ignoreRecord: true),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('তৈরির তারিখ')
                    ->date('d M, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                
                ]);
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