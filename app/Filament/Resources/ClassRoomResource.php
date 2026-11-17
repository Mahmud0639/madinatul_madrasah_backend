<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassRoomResource\Pages;
use App\Models\MadrasahClass; // মূল madrasah_classes মডেল ব্যবহার করা হলো
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClassRoomResource extends Resource
{
    protected static ?string $model = MadrasahClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?string $navigationLabel = 'ক্লাস ম্যানেজমেন্ট';
    protected static ?string $pluralModelLabel = 'ক্লাসসমূহ';
    protected static ?string $modelLabel = 'ক্লাস';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('ক্লাস বা জামাতের নাম (যেমন: মিশকাত জামাত)')
                    ->required()
                    ->maxLength(255)
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
            'index' => Pages\ManageClassRooms::route('/'),
        ];
    }
}