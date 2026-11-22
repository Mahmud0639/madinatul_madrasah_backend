<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\FeesRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\ExamResultsRelationManager;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'ছাত্র-ছাত্রী ব্যবস্থাপনা';
    protected static ?string $pluralModelLabel = 'ছাত্র-ছাত্রীবৃন্দ';
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'roll_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['roll_number', 'rfid_card_number', 'name', 'parent.name', 'parent.phone'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([

                    // ধাপ ১: ব্যক্তিগত তথ্য (সরাসরি ছাত্রের নাম ও জন্মতারিখ)
                    Forms\Components\Wizard\Step::make('ব্যক্তিগত তথ্য')
                        ->description('ছাত্রের মৌলিক তথ্য')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                // ছাত্রের ছবি আপলোডের ফিল্ড (উপরে পুরো জায়গা জুড়ে থাকবে)
                                Forms\Components\FileUpload::make('photo')
                                    ->label('ছাত্রের ছবি (পাসপোর্ট সাইজ)')
                                    ->image()
                                    ->directory('student-photos')
                                    ->imageEditor()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->label('ছাত্রের পুরো নাম')
                                    ->placeholder('যেমন: মাওলানা আব্দুল্লাহ')
                                    ->prefixIcon('heroicon-o-user')
                                    ->extraInputAttributes(['autocomplete' => 'off']),

                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->label('জন্ম তারিখ (ঐচ্ছিক)')
                                    ->prefixIcon('heroicon-o-calendar'),

                                // রক্তের গ্রুপের ড্রপডাউন ফিল্ড
                                Forms\Components\Select::make('blood_group')
                                    ->label('রক্তের গ্রুপ')
                                    ->options([
                                        'A+' => 'A+',
                                        'A-' => 'A-',
                                        'B+' => 'B+',
                                        'B-' => 'B-',
                                        'O+' => 'O+',
                                        'O-' => 'O-',
                                        'AB+' => 'AB+',
                                        'AB-' => 'AB-',
                                    ])
                                    ->searchable()
                                    ->placeholder('রক্তের গ্রুপ নির্বাচন করুন')
                                    ->prefixIcon('heroicon-o-heart'),
                            ]),
                        ]),

                    // ধাপ ২: একাডেমিক তথ্য (বিভাগ, ক্লাস ও রোল)
                    Forms\Components\Wizard\Step::make('একাডেমিক তথ্য')
                        ->description('ক্লাস, রোল ও বিভাগ নির্বাচন')
                        ->icon('heroicon-o-book-open')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([

                                Forms\Components\Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('jamatu_class', null);
                                        $set('roll_number', null);
                                    })
                                    ->label('একাডেমিক বিভাগ')
                                    ->placeholder('বিভাগ নির্বাচন করুন')
                                    ->prefixIcon('heroicon-o-building-library'),

                                Forms\Components\Select::make('jamatu_class')
                                    ->options(function (Forms\Get $get) {
                                        $departmentId = $get('department_id');
                                        if (!$departmentId) {
                                            return [];
                                        }
                                        return \App\Models\MadrasahClass::where('department_id', $departmentId)
                                            ->pluck('name', 'name')
                                            ->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        if ($state) {
                                            $departmentId = $get('department_id');
                                            $maxRoll = Student::where('jamatu_class', $state)
                                                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                                                ->max('roll_number');
                                            $set('roll_number', $maxRoll ? $maxRoll + 1 : 1);
                                        } else {
                                            $set('roll_number', null);
                                        }
                                    })
                                    ->label('জামাত / ক্লাস')
                                    ->placeholder('আগে বিভাগ সিলেক্ট করুন')
                                    ->prefixIcon('heroicon-o-academic-cap'),

                                // ক্লাস রোল নম্বর
                                Forms\Components\TextInput::make('roll_number')
                                    ->required()
                                    ->numeric()
                                    ->label('ক্লাস রোল নম্বর')
                                    ->placeholder('অটোমেটিক আসবে')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->live()
                                    ->rules(fn (Forms\Get $get, ?Student $record): array => [
                                        \Illuminate\Validation\Rule::unique('students', 'roll_number')
                                            ->where('jamatu_class', $get('jamatu_class'))
                                            ->where('department_id', $get('department_id'))
                                            ->ignore($record?->id),
                                    ]),

                                Forms\Components\Select::make('section')
                                    ->options([
                                        'residential' => 'আবাসিক (Residential)',
                                        'non_residential' => 'অনাবাসিক (Non-Residential)',
                                    ])
                                    ->default('non_residential')
                                    ->required()
                                    ->label('আবাসিক / অনাবাসিক')
                                    ->prefixIcon('heroicon-o-home'),

                                Forms\Components\TextInput::make('rfid_card_number')
                                    ->label('RFID কার্ড নম্বর')
                                    ->placeholder('স্মার্ট কার্ড আইডি স্ক্যান করুন')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    // ধাপ ৩: অভিভাবক তথ্য (শুধু অভিভাবক সিলেক্ট বা নতুন তৈরি করার পপআপ)
                    Forms\Components\Wizard\Step::make('অভিভাবক তথ্য')
                        ->description('অভিভাবক বা ফি প্রদানকারী নির্বাচন')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Forms\Components\Select::make('parent_id')
                                ->relationship('parent', 'name', fn (Builder $query) => $query->where('role', 'parent'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('অভিভাবকের নাম / মোবাইল')
                                ->placeholder('অভিভাবক নির্বাচন করুন')
                                ->helperText('যিনি মাদ্রাসায় ফি প্রদান করবেন বা অ্যাপে লগইন করবেন। নতুন অভিভাবক যোগ করতে (+) আইকন ব্যবহার করুন।')
                                ->prefixIcon('heroicon-o-shield-check')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->label('অভিভাবকের পুরো নাম')
                                        ->placeholder('যেমন: মাওলানা আব্দুর রহিম')
                                        ->prefixIcon('heroicon-o-user'),

                                    Forms\Components\TextInput::make('phone')
                                        ->tel()
                                        ->required()
                                        ->unique('users', 'phone')
                                        ->label('মোবাইল নম্বর (লগইন আইডি)')
                                        ->placeholder('01XXXXXXXXX')
                                        ->prefixIcon('heroicon-o-phone'),

                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->label('ইমেইল ঠিকানা (ঐচ্ছিক)')
                                        ->placeholder('example@gmail.com')
                                        ->prefixIcon('heroicon-o-envelope'),

                                    Forms\Components\TextInput::make('password')
                                        ->password()
                                        ->required()
                                        ->default('12345678')
                                        ->label('লগইন পাসওয়ার্ড')
                                        ->placeholder('পাসওয়ার্ড দিন')
                                        ->prefixIcon('heroicon-o-key'),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    $data['role'] = 'parent';
                                    $data['password'] = bcrypt($data['password'] ?? '12345678');

                                    if (empty($data['email'])) {
                                        $data['email'] = $data['phone'] . '@madrasah.local';
                                    }

                                    $user = User::firstOrCreate(
                                        ['phone' => $data['phone']],
                                        $data
                                    );

                                    return $user->id;
                                }),
                        ]),
                ])->submitAction(
                    Forms\Components\Actions\Action::make('submit')
                        ->label('সংরক্ষণ করুন (Create)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->submit('create')
                )
                ->columnSpanFull()
                ->skippable(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('roll_number')
                    ->label('রোল')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('ছাত্রের নাম')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('parent.phone')
                    ->label('অভিভাবকের মোবাইল')
                    ->searchable(['phone'])
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('বিভাগ')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('jamatu_class')
                    ->label('জামাত/ক্লাস')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('section')
                    ->label('ধরণ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'residential' => 'আবাসিক',
                        'non_residential' => 'অনাবাসিক',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'residential' => 'success',
                        'non_residential' => 'warning',
                    }),

                Tables\Columns\TextColumn::make('rfid_card_number')
                    ->label('RFID নম্বর')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('যুক্ত করার তারিখ')
                    ->date('d M, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('বিভাগ ফিল্টার')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('jamatu_class')
                    ->label('জামাত/ক্লাস ফিল্টার')
                    ->options(fn () => Student::distinct()->pluck('jamatu_class', 'jamatu_class')->toArray())
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('section')
                    ->label('আবাসিক/অনাবাসিক ফিল্টার')
                    ->options([
                        'residential' => 'আবাসিক',
                        'non_residential' => 'অনাবাসিক',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info'),
                Tables\Actions\EditAction::make()->color('warning'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('roll_number', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
            FeesRelationManager::class,
            ExamResultsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/index'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}