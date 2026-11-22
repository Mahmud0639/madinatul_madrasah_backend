<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MadrasahRoutineResource\Pages;
use App\Models\MadrasahRoutine;
use App\Models\MadrasahClass;
use App\Models\Subject;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MadrasahRoutineResource extends Resource
{
    protected static ?string $model = MadrasahRoutine::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'ক্লাস রুটিন ও সময়সূচি';
    protected static ?string $pluralModelLabel = 'ক্লাস রুটিনসমূহ';
    protected static ?string $navigationGroup = 'একাডেমিক ম্যানেজমেন্ট';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ক্লাস রুটিন তথ্য সেটআপ')
                    ->description('কোন ক্লাসের কোন বারে কোন পিরিয়ডে কোন কিতাব পড়ানো হবে তা এখানে নির্ধারণ করুন।')
                    ->schema([
                        // ১. ক্লাস সিলেক্ট করুন (Live করা হয়েছে যাতে ক্লাস বদলালে বিষয় পরিবর্তন হয়)
                        Forms\Components\Select::make('madrasah_class_id')
                            ->label('ক্লাস / জামাত সিলেক্ট করুন')
                            ->relationship('madrasahClass', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('subject_name', null)),

                        // ২. বার বা দিন সিলেক্ট করুন
                        Forms\Components\Select::make('day_name')
                            ->label('সপ্তাহের দিন (বার)')
                            ->options([
                                'শনিবার' => 'শনিবার',
                                'রবিবার' => 'রবিবার',
                                'সোমবার' => 'সোমবার',
                                'মঙ্গলবার' => 'মঙ্গলবার',
                                'বুধবার' => 'বুধবার',
                                'বৃহস্পতিবার' => 'বৃহস্পতিবার',
                                'শুক্রবার' => 'শুক্রবার',
                            ])
                            ->required()
                            ->searchable(),

                        // ৩. পিরিয়ডের নাম (ড্রপডাউন, প্লাস আইকন এবং Live যুক্ত করা হয়েছে)
                        Forms\Components\Select::make('period_name')
                            ->label('পিরিয়ডের নাম')
                            ->options([
                                '১ম পিরিয়ড' => '১ম পিরিয়ড',
                                '২য় পিরিয়ড' => '২য় পিরিয়ড',
                                '৩য় পিরিয়ড' => '৩য় পিরিয়ড',
                                '৪র্থ পিরিয়ড' => '৪র্থ পিরিয়ড',
                                '৫ম পিরিয়ড' => '৫ম পিরিয়ড',
                                '৬ষ্ঠ পিরিয়ড' => '৬ষ্ঠ পিরিয়ড',
                                '৭ম পিরিয়ড' => '৭ম পিরিয়ড',
                                '৮ম পিরিয়ড' => '৮ম পিরিয়ড',
                                'বিরতি / টিফিন' => 'বিরতি / টিফিন',
                            ])
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('নতুন পিরিয়ডের নাম (যেমন: বিশেষ ক্লাস)')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                return $data['name'];
                            }),

                        // ৪. সময়কাল (শুরু ও শেষ সময়)
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('শুরুর সময়')
                                    ->seconds(false)
                                    ->required(),
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('শেষের সময়')
                                    ->seconds(false)
                                    ->required(),
                            ])->columnSpan(1),

                   // ৫. বিষয় বা কিতাবের নাম
                        Forms\Components\Select::make('subject_name')
                            ->label('বিষয় বা কিতাবের নাম')
                            ->options(function (callable $get) {
                                $classId = $get('madrasah_class_id');
                                if (!$classId) {
                                    return Subject::query()->pluck('subject_name', 'subject_name')->toArray();
                                }
                                return Subject::where('madrasah_class_id', $classId)
                                    ->pluck('subject_name', 'subject_name')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get) => $get('period_name') !== 'বিরতি / টিফিন')
                            ->disabled(fn (callable $get) => $get('period_name') === 'বিরতি / টিফিন')
                            ->dehydrated(fn (callable $get) => $get('period_name') !== 'বিরতি / টিফিন')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('subject_name')
                                    ->label('নতুন কিতাব বা বিষয়ের নাম')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('code')
                                    ->label('সাবজেক্ট কোড')
                                    ->required()
                                    ->unique('subjects', 'code')
                                    ->maxLength(255)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('generateCode')
                                            ->icon('heroicon-m-arrow-path')
                                            ->action(function (callable $set) {
                                                $code = 'SUB-' . rand(100, 999);
                                                while (Subject::where('code', $code)->exists()) {
                                                    $code = 'SUB-' . rand(100, 999);
                                                }
                                                $set('code', $code);
                                            })
                                    ),
                            ])
                            ->createOptionUsing(function (array $data, callable $get): string {
                                // মূল ফর্মের ক্লাস আইডি সুরক্ষিতভাবে সংগ্রহ করার পদ্ধতি
                                $classId = $get('madrasah_class_id') ?? request()->input('data.madrasah_class_id');

                                $subject = Subject::create([
                                    'madrasah_class_id' => $classId,
                                    'subject_name' => $data['subject_name'],
                                    'code' => $data['code'],
                                ]);
                                return $subject->subject_name;
                            }),

                        // ৬. উস্তাদ বা শিক্ষকের নাম (বিরতি সিলেক্ট করলে ডিসেবল হবে)
                        Forms\Components\Select::make('teacher_name')
                            ->label('উস্তাদ / শিক্ষকের নাম')
                            ->options(function () {
                                return User::where('role', 'teacher')->pluck('name', 'name')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('উস্তাদ নির্বাচন করুন')
                            ->disabled(fn (callable $get) => $get('period_name') === 'বিরতি / টিফিন')
                            ->dehydrated(fn (callable $get) => $get('period_name') !== 'বিরতি / টিফিন')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('শিক্ষকের নাম')
                                    ->required(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('মোবাইল নম্বর')
                                    ->required()
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('ইমেইল')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('password')
                                    ->label('পাসওয়ার্ড')
                                    ->password()
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                $user = User::create([
                                    'name' => $data['name'],
                                    'phone' => $data['phone'],
                                    'email' => $data['email'],
                                    'password' => bcrypt($data['password']),
                                    'role' => 'teacher',
                                ]);
                                return $user->name;
                            }),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('madrasahClass.name')
                    ->label('ক্লাসের নাম')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('day_name')
                    ->label('বার')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_name')
                    ->label('পিরিয়ড')
                    ->searchable(),

                Tables\Columns\TextColumn::make('time_slot')
                    ->label('সময়কাল')
                    ->getStateUsing(fn ($record) => ($record->start_time && $record->end_time) ? date('h:i A', strtotime($record->start_time)) . ' - ' . date('h:i A', strtotime($record->end_time)) : 'নির্ধারিত নয়')
                    ->icon('heroicon-m-clock'),

                Tables\Columns\TextColumn::make('subject_name')
                    ->label('বিষয় / কিতাব')
                    ->placeholder('প্রযোজ্য নয় (বিরতি)')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('teacher_name')
                    ->label('উস্তাদজির নাম')
                    ->placeholder('প্রযোজ্য নয় (বিরতি)')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('madrasah_class_id')
                    ->label('ক্লাস অনুযায়ী ফিল্টার')
                    ->relationship('madrasahClass', 'name'),

                Tables\Filters\SelectFilter::make('day_name')
                    ->label('বার অনুযায়ী ফিল্টার')
                    ->options([
                        'শনিবার' => 'শনিবার',
                        'রবিবার' => 'রবিবার',
                        'সোমবার' => 'সোমবার',
                        'মঙ্গলবার' => 'মঙ্গলবার',
                        'বুধবার' => 'বুধবার',
                        'বৃহস্পতিবার' => 'বৃহস্পতিবার',
                        'শুক্রবার' => 'শুক্রবার',
                    ]),
            ])->headerActions([
                Tables\Actions\Action::make('print_routine')
                    ->label('পিডিএফ প্রিভিউ ও ডাউনলোড')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('madrasah_class_id')
                            ->label('কোন ক্লাসের রুটিন ডাউনলোড করতে চান? (খালি রাখলে সব ক্লাস আসবে)')
                            ->options(MadrasahClass::pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (array $data) {
                        $url = route('routine.print', ['class_id' => $data['madrasah_class_id'] ?? null]);
                        return redirect()->away($url);
                    })
                    ->openUrlInNewTab(),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMadrasahRoutines::route('/index'),
            'create' => Pages\CreateMadrasahRoutine::route('/create'),
            'edit' => Pages\EditMadrasahRoutine::route('/{record}/edit'),
        ];
    }
}