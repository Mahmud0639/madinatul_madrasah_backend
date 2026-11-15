<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\TeacherAttendance;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms;

class ManageTeacherAttendance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'শিক্ষকদের দৈনিক হাজিরা রিপোর্ট';
    protected static ?string $slug = 'teacher-attendance-report';
    protected static string $view = 'filament.pages.manage-teacher-attendance';

    public ?array $data = [];
    public $reportData = [];

    public function mount(): void
    {
        $this->form->fill([
            'selected_date' => now()->format('Y-m-d'),
        ]);
        $this->loadReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('selected_date')
                    ->label('তারিখ নির্বাচন করুন')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->default(now())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->loadReport();
                    }),
            ])
            ->statePath('data');
    }

    public function loadReport()
    {
        $formData = $this->form->getState();
        $selectedDate = $formData['selected_date'] ?? now()->format('Y-m-d');

        // সব শিক্ষককে নিয়ে আসা
        $teachers = User::where('role', 'teacher')->get();
        $report = [];

        foreach ($teachers as $teacher) {
            // ওই নির্দিষ্ট তারিখের হাজিরা খোঁজা
            $attendance = TeacherAttendance::where('user_id', $teacher->id)
                ->whereDate('date', $selectedDate)
                ->first();

            $report[] = [
                'name' => $teacher->name,
                'status' => $attendance ? $attendance->status : 'absent',
                'in_time' => $attendance && $attendance->in_time ? $attendance->in_time : 'নেই',
                'out_time' => $attendance && $attendance->out_time ? $attendance->out_time : 'নেই',
                'lateness' => $attendance && $attendance->lateness_minutes >  0 ? $attendance->lateness_minutes . ' মিনিট' : '০ মিনিট',
                'hours' => $attendance ? $attendance->total_hours . ' ঘণ্টা' : '০ ঘণ্টা',
            ];
        }

        $this->reportData = $report;
    }
}