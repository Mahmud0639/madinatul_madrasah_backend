<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResultResource\Pages;
use App\Models\ExamResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExamResultResource extends Resource
{
    protected static ?string $model = ExamResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'পরীক্ষার ফলাফল';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student.user', 'name')
                    ->required()
                    ->searchable()
                    ->label('ছাত্রের নাম'),
                
                Forms\Components\TextInput::make('exam_name')
                    ->required()
                    ->label('পরীক্ষার নাম'),
                
                Forms\Components\TextInput::make('subject_name')
                    ->required()
                    ->label('বিষয়'),
                
                Forms\Components\TextInput::make('written_marks')
                    ->numeric()
                    ->default(0)
                    ->label('লিখিত নম্বর'),
                
                Forms\Components\TextInput::make('oral_marks')
                    ->numeric()
                    ->default(0)
                    ->label('মৌখিক নম্বর'),
                
                Forms\Components\TextInput::make('total_marks')
                    ->numeric()
                    ->required()
                    ->label('মোট নম্বর'),
                
                Forms\Components\TextInput::make('grade')
                    ->label('গ্রেড (যেমন: A+)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('ছাত্রের নাম')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('exam_name')
                    ->label('পরীক্ষা')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('বিষয়'),
                
                Tables\Columns\TextColumn::make('written_marks')
                    ->label('লিখিত'),
                
                Tables\Columns\TextColumn::make('oral_marks')
                    ->label('মৌখিক'),
                
                Tables\Columns\TextColumn::make('total_marks')
                    ->label('মোট'),
                
                Tables\Columns\TextColumn::make('grade')
                    ->label('গ্রেড')
                    ->badge(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamResults::route('/'),
            'create' => Pages\CreateExamResult::route('/create'),
            'edit' => Pages\EditExamResult::route('/{record}/edit'),
        ];
    }
}