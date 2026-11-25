<?php

namespace App\Filament\Pages;

use App\Models\ExamGradingSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExamGradingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'একাডেমিক ও পরীক্ষা';

    protected static ?string $navigationLabel = 'গ্রেডিং ও পরীক্ষা সেটিংস';

    protected static ?string $title = 'গ্রেডিং সিস্টেম ও মৌখিক পরীক্ষা সেটিংস';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.exam-grading-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = ExamGradingSetting::activeSetting();

        $this->form->fill([
            'name' => $setting->name,
            'grading_mode' => $setting->grading_mode,
            'include_oral' => $setting->include_oral,
            'fail_on_any_subject' => $setting->fail_on_any_subject,
            'grade_ranges' => $setting->grade_ranges ?? ExamGradingSetting::madrasahPreset(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('সাধারণ নিয়ম ও মৌখিক পরীক্ষা')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('সেটিংসের নাম')
                            ->required()
                            ->maxLength(120),

                        Select::make('grading_mode')
                            ->label('গ্রেডিং সিস্টেম ধরণ')
                            ->options([
                                'madrasah' => 'মাদ্রাসা নিয়ম (মুমতাজ, জায়্যিদ ইত্যাদি)',
                                'school' => 'স্কুল / বোর্ড নিয়ম (A+, A, A-, B, C, D, F)',
                                'custom' => 'সম্পূর্ণ কাস্টম গ্রেডিং',
                            ])
                            ->required(),

                        Toggle::make('include_oral')
                            ->label('মৌখিক পরীক্ষা নম্বরে যুক্ত হবে?')
                            ->helperText('বন্ধ রাখলে শুধু লিখিত নম্বর দিয়ে হিসাব হবে; চালু থাকলে লিখিত + মৌখিক যুক্ত হবে।')
                            ->inline(false),

                        Toggle::make('fail_on_any_subject')
                            ->label('এক বিষয়ে ফেল করলে সামগ্রিক ফেল/রাসিব?')
                            ->helperText('চালু থাকলে যেকোনো বিষয়ে ফেল করলে সামগ্রিক ফলাফল ফেল/রাসিব হিসেবে গণ্য হবে।')
                            ->inline(false),
                    ]),

                Section::make('গ্রেড ও নম্বর সীমা তালিকা (নিজের মতো সেট করুন)')
                    ->description('প্রতিটি গ্রেডের নাম, সর্বনিম্ন ও সর্বোচ্চ শতাংশ এবং GPA পয়েন্ট নির্ধারণ করে নিচে সংরক্ষণ করুন।')
                    ->schema([
                        Repeater::make('grade_ranges')
                            ->label('গ্রেড রেঞ্জসমূহ')
                            ->schema([
                                TextInput::make('label')
                                    ->label('গ্রেডের নাম (যেমন: মুমতাজ / A+)')
                                    ->required(),

                                TextInput::make('min_percentage')
                                    ->label('সর্বনিম্ন (%)')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('max_percentage')
                                    ->label('সর্বোচ্চ (%)')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('gpa')
                                    ->label('GPA পয়েন্ট')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0),

                                Toggle::make('is_pass')
                                    ->label('পাস গ্রেড?')
                                    ->default(true),

                                Toggle::make('active')
                                    ->label('সক্রিয়')
                                    ->default(true),
                            ])
                            ->columns(6)
                            ->addActionLabel('+ নতুন গ্রেড যুক্ত করুন')
                            ->reorderable()
                            ->collapsible(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loadMadrasahPreset')
                ->label('মাদ্রাসা প্রিসেট লোড')
                ->color('info')
                ->action(function () {
                    $this->form->fill([
                        ...$this->form->getState(),
                        'grading_mode' => 'madrasah',
                        'grade_ranges' => ExamGradingSetting::madrasahPreset(),
                    ]);

                    Notification::make()
                        ->title('মাদ্রাসা তাকদির প্রিসেট লোড হয়েছে')
                        ->body('প্রয়োজনমতো পরিবর্তন করে "সংরক্ষণ করুন" বাটনে চাপ দিন।')
                        ->success()
                        ->send();
                }),

            Action::make('loadSchoolPreset')
                ->label('স্কুল প্রিসেট লোড')
                ->color('warning')
                ->action(function () {
                    $this->form->fill([
                        ...$this->form->getState(),
                        'grading_mode' => 'school',
                        'grade_ranges' => ExamGradingSetting::schoolPreset(),
                    ]);

                    Notification::make()
                        ->title('স্কুল / বোর্ড গ্রেড প্রিসেট লোড হয়েছে')
                        ->body('প্রয়োজনমতো পরিবর্তন করে "সংরক্ষণ করুন" বাটনে চাপ দিন।')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $setting = ExamGradingSetting::activeSetting();

        $setting->update([
            'name' => $state['name'],
            'grading_mode' => $state['grading_mode'],
            'include_oral' => (bool) ($state['include_oral'] ?? true),
            'fail_on_any_subject' => (bool) ($state['fail_on_any_subject'] ?? true),
            'grade_ranges' => $state['grade_ranges'] ?? [],
            'is_active' => true,
        ]);

        Notification::make()
            ->title('গ্রেডিং ও পরীক্ষা সেটিংস সংরক্ষিত হয়েছে')
            ->success()
            ->send();
    }
}