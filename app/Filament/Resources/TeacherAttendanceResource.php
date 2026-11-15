<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherAttendanceResource\Pages;
use App\Models\TeacherAttendance;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Unique;

class TeacherAttendanceResource extends Resource
{
    protected static ?string $model = TeacherAttendance::class;

    protected static ?string $navigationGroup = 'হাজিরা ব্যবস্থাপনা';
    protected static ?string $navigationLabel = 'দৈনিক হাজিরা রিপোর্ট (শিক্ষক)';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('শিক্ষক নির্বাচন করুন')
                    ->options(User::where('role', 'teacher')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->rule(function (Forms\Get $get, ?TeacherAttendance $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $date = $get('date');
                            if (!$date) return;

                            $query = TeacherAttendance::where('user_id', $value)
                                ->where('date', $date);

                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('এই শিক্ষকের এই তারিখের হাজিরা ইতিমধ্যে এন্ট্রি করা হয়েছে। একই দিনে একাধিক এন্ট্রি করা যাবে না।');
                            }
                        };
                    }),

                Forms\Components\DatePicker::make('date')
                    ->label('তারিখ')
                    ->required()
                    ->default(now())
                    ->live()
                    ->rule(function (Forms\Get $get, ?TeacherAttendance $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $userId = $get('user_id');
                            if (!$userId) return;

                            $query = TeacherAttendance::where('user_id', $userId)
                                ->where('date', $value);

                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('এই শিক্ষকের এই তারিখের হাজিরা ইতিমধ্যে এন্ট্রি করা হয়েছে। একই দিনে একাধিক এন্ট্রি করা যাবে না।');
                            }
                        };
                    }),

                Forms\Components\Select::make('status')
                    ->label('উপস্থিতির স্ট্যাটাস')
                    ->options([
                        'present' => 'উপস্থিত (Present)',
                        'late' => 'বিলম্বে উপস্থিত (Late)',
                        'absent' => 'অনুপস্থিত (Absent)',
                    ])
                    ->required()
                    ->default('present')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state === 'absent') {
                            // অনুপস্থিত হলে ইন-টাইম ও আউট-টাইম ফাঁকা হবে
                            $set('in_time', null);
                            $set('out_time', null);
                        } else {
                            // উপস্থিত বা লেট হলে আউট-টাইমে ডিফল্ট সময় সেট হয়ে যাবে
                            $set('out_time', cache('office_end_time', '14:00'));
                        }
                    }),

                Forms\Components\TimePicker::make('in_time')
                    ->label('প্রবেশের সময় (In Time)')
                    ->default(cache('office_start_time', '08:00'))
                    ->seconds(false)
                    ->live()
                    ->disabled(fn (Forms\Get $get): bool => $get('status') === 'absent')
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if (!$state) return;

                        $startTime = cache('office_start_time', '08:00');
                        $graceMinutes = (int) cache('office_grace_minutes', 15);

                        $startMinutes = Carbon::parse($startTime)->hour * 60 + Carbon::parse($startTime)->minute;
                        $inMinutes = Carbon::parse($state)->hour * 60 + Carbon::parse($state)->minute;

                        // ইন-টাইম অনুযায়ী স্ট্যাটাস অটোমেটিক পরিবর্তন হবে
                        if ($inMinutes > ($startMinutes + $graceMinutes)) {
                            $set('status', 'late');
                        } else {
                            $set('status', 'present');
                        }
                    }),

                Forms\Components\TimePicker::make('out_time')
                    ->label('প্রস্থানের সময় (Out Time)')
                    ->default(cache('office_end_time', '14:00'))
                    ->seconds(false)
                    // আউট-টাইমের বক্সটি সবসময় ডিসএবল বা লক থাকবে, তবে মান সেট করা থাকবে
                    ->disabled(true)
                    ->dehydrated(true), // যাতে ডিসএবল থাকার পরেও ডাটাবেজে ডাটা সেভ হয়
            ]);
    }

   public static function table(Table $table): Table
    {
        return $table
            // ১. ডিফল্টভাবে নতুন তারিখ সবার উপরে থাকবে
            ->defaultSort('date', 'desc')
            
            // ২. তারিখ অনুযায়ী সুন্দর গ্রুপ করে সাজানো থাকবে (হিজিবিজি লাগবে না)
            ->groups([
                Tables\Grouping\Group::make('date')
                    ->label('তারিখ')
                    ->date('d M, Y')
                    ->collapsible(),
                Tables\Grouping\Group::make('teacher.name')
                    ->label('শিক্ষক')
                    ->collapsible(),
            ])
            ->defaultGroup('date') // ডিফল্টভাবে তারিখ অনুযায়ী গ্রুপ হয়ে থাকবে

            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('শিক্ষকের নাম')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('date')
                    ->label('তারিখ')
                    ->date('d M, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'উপস্থিত',
                        'late' => 'বিলম্ব',
                        'absent' => 'অনুপস্থিত',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'present',
                        'warning' => 'late',
                        'danger' => 'absent',
                    ]),

                Tables\Columns\TextColumn::make('in_time')
                    ->label('ইন-টাইম')
                    ->time('h:i A') // 08:00 AM ফরম্যাটে দেখাবে
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('out_time')
                    ->label('আউট-টাইম')
                    ->time('h:i A') // 02:00 PM ফরম্যাটে দেখাবে
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('মোট কর্মঘণ্টা')
                    ->suffix(' ঘণ্টা')
                    ->alignCenter(),
            ])
            ->filters([
                // ৩. শিক্ষক অনুযায়ী ফিল্টার
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('শিক্ষক নির্বাচন করুন')
                    ->options(User::where('role', 'teacher')->pluck('name', 'id'))
                    ->searchable(),

                // ৪. স্ট্যাটাস অনুযায়ী ফিল্টার
                Tables\Filters\SelectFilter::make('status')
                    ->label('স্ট্যাটাস')
                    ->options([
                        'present' => 'উপস্থিত',
                        'late' => 'বিলম্ব',
                        'absent' => 'অনুপস্থিত',
                    ]),

                // ৫. নির্দিষ্ট তারিখ অনুযায়ী ফিল্টার
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('selected_date')
                            ->label('তারিখ অনুযায়ী খুঁজুন'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['selected_date'],
                            fn ($q, $date) => $q->whereDate('date', $date)
                        );
                    }),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherAttendances::route('/'),
            'create' => Pages\CreateTeacherAttendance::route('/create'),
            'edit' => Pages\EditTeacherAttendance::route('/{record}/edit'),
        ];
    }
}