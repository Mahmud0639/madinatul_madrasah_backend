<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'examResults';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('exam_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('exam_name')
        ->columns([
            Tables\Columns\TextColumn::make('exam_name')->label('পরীক্ষার নাম'),
            Tables\Columns\TextColumn::make('subject_name')->label('বিষয়'),
            Tables\Columns\TextColumn::make('written_marks')->label('লিখিত'),
            Tables\Columns\TextColumn::make('oral_marks')->label('মৌখিক'),
            Tables\Columns\TextColumn::make('total_marks')->label('মোট'),
            Tables\Columns\TextColumn::make('grade')->label('গ্রেড')->badge(),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make()->label('রেজাল্ট যোগ করুন'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
}
