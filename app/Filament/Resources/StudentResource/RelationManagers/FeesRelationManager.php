<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class FeesRelationManager extends RelationManager
{
    protected static string $relationship = 'fees';

    protected static ?string $title = 'ফি ও বকেয়া তালিকা';

    protected static function syncStatusFromAmounts(Get $get, Set $set): void
    {
        $amount = (float) ($get('amount') ?? 0);
        $paid = (float) ($get('paid_amount') ?? 0);

        if ($amount > 0 && $paid >= $amount) {
            $set('status', 'paid');
        } elseif ($paid > 0 && $paid < $amount) {
            $set('status', 'partial');
        } else {
            $set('status', 'unpaid');
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('fee_title')
                    ->label('ফি-এর খাত ও কারণ')
                    ->placeholder('যেমন: জানুয়ারি ২০২৬ — মাসিক বেতন / খাবার ফি')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('amount')
                    ->label('মোট নির্ধারিত টাকা')
                    ->numeric()
                    ->prefix('৳')
                    ->default(0)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::syncStatusFromAmounts($get, $set)),

                Forms\Components\TextInput::make('paid_amount')
                    ->label('জমা দেওয়া টাকা')
                    ->numeric()
                    ->prefix('৳')
                    ->default(0)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::syncStatusFromAmounts($get, $set)),

                Forms\Components\Select::make('status')
                    ->label('পরিশোধের অবস্থা')
                    ->options([
                        'paid' => 'পরিশোধিত (Paid)',
                        'partial' => 'আংশিক পরিশোধিত (Partial)',
                        'unpaid' => 'বকেয়া (Unpaid)',
                    ])
                    ->default('unpaid')
                    ->required(),

                Forms\Components\DatePicker::make('due_date')
                    ->label('মেয়াদ / বিল তারিখ'),
            ]);
    }

    public function table(Table $table): Table
    {
        $studentId = $this->getOwnerRecord()->getKey();
        $allFees = \App\Models\Fee::query()->where('student_id', $studentId)->get();

        $totalAmount = round((float) $allFees->sum('amount'), 2);
        $totalPaid = round((float) $allFees->sum('paid_amount'), 2);
        $totalDue = round((float) $allFees->sum(fn ($f) => max(0, (float) $f->amount - (float) $f->paid_amount)), 2);
        $dueColor = $totalDue > 0 ? '#ef4444' : '#22c55e';

        return $table
            ->recordTitleAttribute('fee_title')
            ->description(new HtmlString("
                <div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-top:6px;margin-bottom:4px;'>
                    <div style='padding:12px 14px;border-radius:10px;border:1px solid rgba(156,163,175,0.22);background:rgba(156,163,175,0.05);'>
                        <div style='font-size:11.5px;opacity:0.75;'>মোট নির্ধারিত ফি</div>
                        <div style='font-size:18px;font-weight:800;margin-top:2px;'>৳{$totalAmount}</div>
                    </div>
                    <div style='padding:12px 14px;border-radius:10px;border:1px solid rgba(34,197,94,0.3);background:rgba(34,197,94,0.07);'>
                        <div style='font-size:11.5px;opacity:0.75;'>মোট জমা হয়েছে</div>
                        <div style='font-size:18px;font-weight:800;color:#22c55e;margin-top:2px;'>৳{$totalPaid}</div>
                    </div>
                    <div style='padding:12px 14px;border-radius:10px;border:1px solid {$dueColor};background:rgba(239,68,68,0.06);'>
                        <div style='font-size:11.5px;opacity:0.75;'>বর্তমান মোট বকেয়া (Due)</div>
                        <div style='font-size:18px;font-weight:800;color:{$dueColor};margin-top:2px;'>৳{$totalDue}</div>
                    </div>
                </div>
            "))
            ->columns([
                Tables\Columns\TextColumn::make('fee_title')
                    ->label('ফি-এর খাত ও কারণ')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('মোট টাকা')
                    ->money('BDT', locale: 'en')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('জমা দেওয়া')
                    ->money('BDT', locale: 'en')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_amount')
                    ->label('বকেয়া (Due)')
                    ->money('BDT', locale: 'en')
                    ->weight('bold')
                    ->color(fn ($record): string => $record->due_amount > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paid' => 'পরিশোধিত',
                        'partial' => 'আংশিক',
                        'unpaid' => 'বকেয়া',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('মেয়াদ / তারিখ')
                    ->date('d M, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('অবস্থা অনুযায়ী')
                    ->options([
                        'paid' => 'পরিশোধিত',
                        'partial' => 'আংশিক',
                        'unpaid' => 'বকেয়া',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('নতুন ফি যুক্ত করুন'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}