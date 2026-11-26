<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherPayrollResource\Pages;
use App\Models\TeacherPayroll;
use App\Models\TeacherSalaryPayment;
use App\Models\TeacherSalaryStructure;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherPayrollResource extends Resource
{
    protected static ?string $model = TeacherPayroll::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-bangladeshi';

    protected static ?string $navigationGroup = 'হিসাব ও ফাইন্যান্স';

    protected static ?string $navigationLabel = 'মাসিক বেতন শিট';

    protected static ?string $modelLabel = 'মাসিক বেতন';

    protected static ?string $pluralModelLabel = 'মাসিক বেতন শিট';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('শিক্ষক ও মাস নির্বাচন')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('শিক্ষক')
                            ->options(fn () => static::getTeacherOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    return;
                                }
                                $structure = TeacherSalaryStructure::query()
                                    ->where('user_id', $state)
                                    ->where('is_active', true)
                                    ->latest('effective_from')
                                    ->first();

                                if ($structure) {
                                    $set('basic_salary', (float) $structure->basic_salary);
                                    $set('allowances', (float) $structure->house_rent + (float) $structure->other_allowance);
                                }
                            })
                            ->required(),

                        Forms\Components\TextInput::make('payroll_month')
                            ->label('বেতনের মাস (YYYY-MM)')
                            ->placeholder('2026-11')
                            ->default(now()->format('Y-m'))
                            ->required(),

                        Forms\Components\TextInput::make('voucher_no')
                            ->label('ভাউচার নম্বর')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('বেতন, বোনাস ও কর্তন (হুজুরের নিয়ন্ত্রণ)')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('basic_salary')
                            ->label('মূল বেতন (৳)')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->required(),

                        Forms\Components\TextInput::make('allowances')
                            ->label('ভাতা (৳)')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        Forms\Components\TextInput::make('bonus')
                            ->label('বোনাস (৳)')
                            ->numeric()
                            ->default(0)
                            ->live(),

                        Forms\Components\TextInput::make('absent_days')
                            ->label('অননুমোদিত অনুপস্থিতি (দিন)')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $basic = (float) ($get('basic_salary') ?? 0);
                                $dailyRate = $basic > 0 ? ($basic / 30) : 0;
                                $autoDed = round($dailyRate * (int) ($state ?? 0), 2);
                                $set('calculated_deduction', $autoDed);
                            }),

                        Forms\Components\TextInput::make('late_days')
                            ->label('বিলম্ব / লেট (দিন)')
                            ->numeric()
                            ->default(0),

                        Forms\Components\TextInput::make('calculated_deduction')
                            ->label('সিস্টেমের হিসাবকৃত কর্তন (৳)')
                            ->numeric()
                            ->default(0)
                            ->helperText('মূল বেতন ÷ ৩০ অনুযায়ী অনুপস্থিতির প্রস্তাবিত কর্তন'),

                        Forms\Components\TextInput::make('manual_deduction')
                            ->label('মুহতামিম / হুজুরের নির্ধারিত কর্তন (৳)')
                            ->numeric()
                            ->nullable()
                            ->helperText('খালি রাখলে সিস্টেমের হিসাবকৃত কর্তন কার্যকর হবে; মান দিলে এটিই চূড়ান্ত হবে'),

                        Forms\Components\TextInput::make('paid_amount')
                            ->label('ইতিমধ্যে পরিশোধিত (৳)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('deduction_note')
                            ->label('কর্তন বা ছাড়ের কারণ / হুজুরের নির্দেশনা')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payroll_month')
                    ->label('মাস')
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('শিক্ষক')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('basic_salary')
                    ->label('মূল বেতন')
                    ->money('BDT'),

                Tables\Columns\TextColumn::make('final_deduction')
                    ->label('চূড়ান্ত কর্তন')
                    ->money('BDT')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('net_salary')
                    ->label('নেট প্রদেয়')
                    ->money('BDT')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('পরিশোধিত')
                    ->money('BDT')
                    ->color('success'),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label('বকেয়া')
                    ->money('BDT')
                    ->color('warning'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('অবস্থা')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'পরিশোধিত',
                        'partial' => 'আংশিক',
                        default => 'বকেয়া',
                    })
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger' => 'unpaid',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('অবস্থা')
                    ->options([
                        'unpaid' => 'বকেয়া',
                        'partial' => 'আংশিক',
                        'paid' => 'পরিশোধিত',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('pay_salary')
                    ->label('বেতন দিন')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (TeacherPayroll $record): bool => (float) $record->due_amount > 0)
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('পরিশোধের পরিমাণ (৳)')
                            ->numeric()
                            ->default(fn (TeacherPayroll $record) => (float) $record->due_amount)
                            ->required(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('পরিশোধের তারিখ')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('payment_method')
                            ->label('মাধ্যম')
                            ->options([
                                'cash' => 'নগদ',
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'bank' => 'ব্যাংক',
                            ])
                            ->default('cash')
                            ->required(),

                        Forms\Components\TextInput::make('note')
                            ->label('নোট'),
                    ])
                    ->action(function (TeacherPayroll $record, array $data): void {
                        TeacherSalaryPayment::create([
                            'teacher_payroll_id' => $record->id,
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'note' => $data['note'] ?? null,
                            'received_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('বেতন পরিশোধ রেকর্ড হয়েছে')
                            ->success()
                            ->send();
                    }),

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
            'index' => Pages\ListTeacherPayrolls::route('/'),
            'create' => Pages\CreateTeacherPayroll::route('/create'),
            'edit' => Pages\EditTeacherPayroll::route('/{record}/edit'),
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