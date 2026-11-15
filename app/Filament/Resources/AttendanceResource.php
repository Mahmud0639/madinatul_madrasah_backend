<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'হাজিরা (Attendance)';
    protected static ?string $slug = 'attendances';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('ছাত্রের নাম'),
                
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->label('তারিখ'),
                
                Forms\Components\TimePicker::make('in_time')
                    ->label('প্রবেশের সময়'),
                
                Forms\Components\TimePicker::make('out_time')
                    ->label('প্রস্থানের সময়'),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'উপস্থিত (Present)',
                        'absent' => 'অনুপস্থিত (Absent)',
                        'late' => 'বিলম্ব (Late)',
                    ])
                    ->default('present')
                    ->required()
                    ->label('স্ট্যাটাস'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('ছাত্রের নাম')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('date')
                    ->label('তারিখ')
                    ->date('d M, Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('in_time')
                    ->label('ইন-টাইম'),
                
                Tables\Columns\TextColumn::make('out_time')
                    ->label('আউট-টাইম'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('স্ট্যাটাস')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'present' => 'উপস্থিত',
                        'absent' => 'অনুপস্থিত',
                        'late' => 'বিলম্ব',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'উপস্থিত',
                        'absent' => 'অনুপস্থিত',
                        'late' => 'বিলম্ব',
                    ])
                    ->label('স্ট্যাটাস ফিল্টার'),
                
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('নির্দিষ্ট তারিখ'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', $date),
                        )),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
