<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonTemplateResource\Pages;
use App\Models\DailyLesson;
use App\Models\LessonTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LessonTemplateResource extends Resource
{
    protected static ?string $model = LessonTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'পড়ার টেমপ্লেটসমূহ';
    protected static ?string $pluralModelLabel = 'পড়ার টেমপ্লেট';
    protected static ?string $navigationGroup = 'একাডেমিক ও পাঠদান';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('কুইক পড়া টেমপ্লেট')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('টেমপ্লেটের নাম')
                            ->placeholder('যেমন: নূরানী / নাজেরা সবক')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('lesson_type')
                            ->label('পড়ার ধরন')
                            ->options(DailyLesson::typeLabels())
                            ->default('sabak')
                            ->required(),

                        Forms\Components\Textarea::make('body_template')
                            ->label('ডিফল্ট লেখার ফরম্যাট')
                            ->placeholder("কায়দা/কিতাব পৃষ্ঠা: \nপড়ার নিয়ম: \nআগামীকাল শোনানো/জমা: ")
                            ->rows(5)
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('সক্রিয় রাখুন')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('টেমপ্লেটের নাম')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('lesson_type')
                    ->label('ধরন')
                    ->badge()
                    ->formatStateUsing(fn ($state) => DailyLesson::typeLabels()[$state] ?? $state),

                Tables\Columns\TextColumn::make('body_template')
                    ->label('ফরম্যাট')
                    ->limit(60),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('সক্রিয়')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessonTemplates::route('/index'),
            'create' => Pages\CreateLessonTemplate::route('/create'),
            'edit' => Pages\EditLessonTemplate::route('/{record}/edit'),
        ];
    }
}