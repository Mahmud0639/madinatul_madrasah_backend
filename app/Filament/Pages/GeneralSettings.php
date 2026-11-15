<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;

class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'অফিস সেটিংস';
    protected static ?string $navigationGroup = 'অফিস ও ফাইন্যান্স';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'general-settings';
    protected static string $view = 'filament.pages.general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'office_start_time' => Cache::get('office_start_time', '08:00'),
            'office_end_time' => Cache::get('office_end_time', '14:00'), // নতুন ডিফল্ট প্রস্থান সময়
            'grace_minutes' => Cache::get('office_grace_minutes', 15),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TimePicker::make('office_start_time')
                    ->label('অফিস শুরুর সময় (Office Start Time)')
                    ->required()
                    ->seconds(false),

                TimePicker::make('office_end_time')
                    ->label('অফিস শেষের সময় (Office End Time)')
                    ->required()
                    ->seconds(false),

                TextInput::make('grace_minutes')
                    ->label('গ্রেস পিরিয়ড মিনিট (Grace Minutes)')
                    ->numeric()
                    ->required()
                    ->default(15),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Cache::put('office_start_time', $data['office_start_time']);
        Cache::put('office_end_time', $data['office_end_time']);
        Cache::put('office_grace_minutes', (int) $data['grace_minutes']);

        Notification::make()
            ->success()
            ->title('সফলভাবে সংরক্ষিত হয়েছে!')
            ->body('অফিস টাইম এবং গ্রেস মিনিট আপডেট করা হয়েছে।')
            ->send();
    }
}