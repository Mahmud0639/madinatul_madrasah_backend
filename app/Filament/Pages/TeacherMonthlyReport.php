<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\TeacherAttendance;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms;

class TeacherMonthlyReport extends Page implements HasForms
{
    use InteractsWithForms;

    // protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    // protected static ?string $navigationLabel = 'শিক্ষকদের মাসিক ও পাই চার্ট রিপোর্ট';
    protected static ?string $slug = 'teacher-monthly-report';
    protected static string $view = 'filament.pages.teacher-monthly-report';

    protected static ?string $navigationGroup = 'হাজিরা ব্যবস্থাপনা';
    protected static ?string $navigationLabel = 'মাসিক ও পাই চার্ট রিপোর্ট (শিক্ষক)';
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?int $navigationSort = 2;

    public ?array $data = [];
    public $summary = [];

    public function mount(): void
    {
        $firstTeacher = User::where('role', 'teacher')->first();

        $this->form->fill([
            'teacher_id' => $firstTeacher ? $firstTeacher->id : null,
            'month' => now()->format('Y-m'),
        ]);

        $this->loadReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->label('শিক্ষক নির্বাচন করুন')
                    ->options(User::where('role', 'teacher')->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadReport()),

                Forms\Components\Select::make('month')
                    ->label('মাস নির্বাচন করুন')
                    ->options(function () {
                        $months = [];
                        // শুধু পেছনের ১ বছর থেকে শুরু করে বর্তমান বছর পর্যন্ত মাসগুলো দেখাবে, সামনের বছরগুলো দেখাবে না
                        for ($year = now()->subYear()->year; $year <= now()->year; $year++) {
                            for ($m = 1; $m <= 12; $m++) {
                                $key = sprintf('%d-%02d', $year, $m);
                                
                                $monthNames = [
                                    1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল', 
                                    5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট', 
                                    9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর'
                                ];
                                
                                $months[$key] = $monthNames[$m] . ' ' . $year;
                            }
                        }
                        return $months;
                    })
                    ->default(now()->format('Y-m'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadReport()),
            ])
            ->statePath('data');
    }

    public function loadReport()
    {
        $formData = $this->form->getState();
        $teacherId = $formData['teacher_id'] ?? null;
        $selectedMonth = $formData['month'] ?? now()->format('Y-m');

        if (!$teacherId) {
            $this->summary = [];
            return;
        }

        $parts = explode('-', $selectedMonth);
        $year = $parts[0] ?? now()->year;
        $month = $parts[1] ?? now()->month;

        $attendances = TeacherAttendance::where('user_id', $teacherId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();

        $totalPresent = $attendances->where('status', 'present')->count();
        $totalLate = $attendances->where('status', 'late')->count();
        $totalAbsent = $attendances->where('status', 'absent')->count();
        $totalHours = $attendances->sum('total_hours');
        $totalLatenessMinutes = $attendances->sum('lateness_minutes');

        $this->summary = [
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_absent' => $totalAbsent,
            'total_hours' => round($totalHours, 2),
            'total_lateness_minutes' => $totalLatenessMinutes,
            'records' => $attendances,
        ];
    }
}