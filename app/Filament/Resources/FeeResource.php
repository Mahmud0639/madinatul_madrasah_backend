<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeeResource\Pages;
use App\Models\Fee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeeResource extends Resource
{
    protected static ?string $model = Fee::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'ফি ও বেতন';
    protected static ?string $pluralModelLabel = 'ফি ও বেতন তালিকা';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student.user', 'name')
                    ->required()
                    ->searchable()
                    ->label('ছাত্রের নাম'),
                
                Forms\Components\TextInput::make('fee_title')
                    ->required()
                    ->label('ফি-এর খাতের নাম'),
                
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label('মোট টাকা'),
                
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->default(0)
                    ->label('জমা দেওয়া টাকা'),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'paid' => 'পরিশোধিত (Paid)',
                        'unpaid' => 'বকেয়া (Unpaid)',
                        'partial' => 'আংশিক (Partial)',
                    ])
                    ->default('unpaid')
                    ->label('স্ট্যাটাস'),
                
                Forms\Components\DatePicker::make('due_date')
                    ->required()
                    ->label('পরিশোধের শেষ তারিখ'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('ছাত্রের নাম')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('fee_title')
                    ->label('ফি-এর খাতা')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('পরিমাণ (টাকা)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('জমা টাকা'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'partial' => 'warning',
                    }),
                
                Tables\Columns\TextColumn::make('due_date')
                    ->label('মেয়াদ')
                    ->date(),
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
            'index' => Pages\ListFees::route('/'),
            'create' => Pages\CreateFee::route('/create'),
            'edit' => Pages\EditFee::route('/{record}/edit'),
        ];
    }
}