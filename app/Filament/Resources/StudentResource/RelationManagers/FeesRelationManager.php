<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FeesRelationManager extends RelationManager
{
    protected static string $relationship = 'fees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('fee_title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('fee_title')
        ->columns([
            Tables\Columns\TextColumn::make('fee_title')->label('ফি-এর খাত'),
            Tables\Columns\TextColumn::make('amount')->label('মোট টাকা'),
            Tables\Columns\TextColumn::make('paid_amount')->label('জমা দেওয়া টাকা'),
            Tables\Columns\TextColumn::make('status')
                ->label('স্ট্যাটাস')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'unpaid' => 'danger',
                    'partial' => 'warning',
                }),
            Tables\Columns\TextColumn::make('due_date')->label('মেয়াদ')->date(),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make()->label('নতুন ফি যুক্ত করুন'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
}
