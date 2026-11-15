<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

   public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->label('নাম')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('phone')
                ->label('মোবাইল নম্বর')
                ->tel()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('ইমেইল (ঐচ্ছিক)')
                ->email()
                ->maxLength(255),

            Forms\Components\TextInput::make('password')
                ->label('পাসওয়ার্ড')
                ->password()
                ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create'),

            Forms\Components\Select::make('role')
                ->label('রোল (Role)')
                ->options([
                    'admin' => 'Admin (অ্যাডমিন)',
                    'teacher' => 'Teacher (শিক্ষক)',
                    'student' => 'Student (ছাত্র)',
                    'parent' => 'Parent (অভিভাবক)',
                ])
                ->required()
                ->default('teacher'),
        ]);
}

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            \Filament\Tables\Columns\TextColumn::make('name')
                ->label('নাম')
                ->searchable()
                ->sortable(),

            \Filament\Tables\Columns\TextColumn::make('phone')
                ->label('মোবাইল নম্বর')
                ->searchable(),

            \Filament\Tables\Columns\TextColumn::make('email')
                ->label('ইমেইল')
                ->searchable(),

            \Filament\Tables\Columns\TextColumn::make('role')
                ->label('রোল')
                ->badge()
                ->colors([
                    'danger' => 'admin',
                    'warning' => 'teacher',
                    'success' => 'student',
                    'info' => 'parent',
                ])
                ->sortable(),

            \Filament\Tables\Columns\TextColumn::make('created_at')
                ->label('তৈরির সময়')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            //
        ])
        ->actions([
            \Filament\Tables\Actions\EditAction::make(),
            \Filament\Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            \Filament\Tables\Actions\BulkActionGroup::make([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
