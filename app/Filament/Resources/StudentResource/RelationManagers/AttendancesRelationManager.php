<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;
use Carbon\Carbon;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->label('তারিখ'),
                
                Forms\Components\TimePicker::make('in_time')
                    ->label('প্রবেশের সময়')
                    ->default('08:00:00'),
                
                Forms\Components\TimePicker::make('out_time')
                    ->label('প্রস্থানের সময়')
                    ->default('16:00:00'),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'উপস্থিত (Present)',
                        'absent' => 'অনুপস্থিত (Absent)',
                        'late' => 'বিলম্ব (Late)',
                    ])
                    ->default('present')
                    ->label('স্ট্যাটাস'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('তারিখ')->date()->sortable(),
                Tables\Columns\TextColumn::make('in_time')->label('ইন-টাইম'),
                Tables\Columns\TextColumn::make('out_time')->label('আউট-টাইম'),
                Tables\Columns\TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                    }),
            ])
            // এখানে টেবিলের জন্য ক্যালেন্ডার ডেট ফিল্টার যুক্ত করা হলো
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('selected_date')
                            ->label('তারিখ অনুযায়ী ফিল্টার করুন')
                            ->native(false), // সুন্দর ক্যালেন্ডার পপআপের জন্য
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['selected_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', $date),
                        );
                    })
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('নতুন হাজিরা দিন'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    // ট্যাবের সংখ্যা কমিয়ে শুধু জরুরি ট্যাবগুলো রাখা হলো
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('সকল হাজিরা'),

            'today' => Tab::make('আজকে')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', Carbon::today())),

            'yesterday' => Tab::make('গতকাল')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('date', Carbon::yesterday())),
        ];
    }
}