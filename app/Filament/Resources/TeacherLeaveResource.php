<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherLeaveResource\Pages;
use App\Models\TeacherLeave;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherLeaveResource extends Resource
{
    protected static ?string $model = TeacherLeave::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'হাজিরা ও ছুটি';

    protected static ?string $navigationLabel = 'শিক্ষকের ছুটি';

    protected static ?string $modelLabel = 'ছুটির আবেদন';

    protected static ?string $pluralModelLabel = 'শিক্ষকের ছুটি';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ছুটির বিবরণ')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('শিক্ষক')
                            ->options(fn () => static::getTeacherOptions())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('leave_type')
                            ->label('ছুটির ধরন')
                            ->options([
                                'casual' => 'নৈমিত্তিক ছুটি',
                                'medical' => 'অসুস্থতাজনিত ছুটি',
                                'emergency' => 'জরুরি ছুটি',
                                'other' => 'অন্যান্য',
                            ])
                            ->default('casual')
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('ছুটি শুরুর তারিখ')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('ছুটি শেষের তারিখ')
                            ->afterOrEqual('start_date')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('অনুমোদনের অবস্থা')
                            ->options([
                                'pending' => 'অপেক্ষমাণ',
                                'approved' => 'অনুমোদিত',
                                'rejected' => 'প্রত্যাখ্যাত',
                            ])
                            ->default('pending')
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if ($state === 'approved') {
                                    $set('approved_by', auth()->id());
                                    $set('approved_at', now()->toDateTimeString());
                                }
                            })
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('ছুটির কারণ')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\Hidden::make('approved_by'),

                        Forms\Components\Hidden::make('approved_at'),
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

                Tables\Columns\TextColumn::make('start_date')
                    ->label('শুরু')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('শেষ')
                    ->date('d M, Y'),

                Tables\Columns\BadgeColumn::make('leave_type')
                    ->label('ধরন')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'medical' => 'অসুস্থতা',
                        'emergency' => 'জরুরি',
                        'other' => 'অন্যান্য',
                        default => 'নৈমিত্তিক',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('অবস্থা')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'approved' => 'অনুমোদিত',
                        'rejected' => 'প্রত্যাখ্যাত',
                        default => 'অপেক্ষমাণ',
                    })
                    ->colors([
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'warning' => 'pending',
                    ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('অবস্থা')
                    ->options([
                        'pending' => 'অপেক্ষমাণ',
                        'approved' => 'অনুমোদিত',
                        'rejected' => 'প্রত্যাখ্যাত',
                    ]),
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
            'index' => Pages\ListTeacherLeaves::route('/'),
            'create' => Pages\CreateTeacherLeave::route('/create'),
            'edit' => Pages\EditTeacherLeave::route('/{record}/edit'),
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