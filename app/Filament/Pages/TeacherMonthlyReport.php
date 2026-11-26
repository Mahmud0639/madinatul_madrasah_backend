<?php

namespace App\Filament\Pages;

use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class TeacherMonthlyReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'teacher-monthly-report';

    protected static string $view = 'filament.pages.teacher-monthly-report';

    protected static ?string $navigationGroup = 'হাজিরা ব্যবস্থাপনা';

    protected static ?string $navigationLabel = 'মাসিক ও পাই চার্ট রিপোর্ট (শিক্ষক)';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'শিক্ষকদের মাসিক হাজিরা ও পারফরম্যান্স রিপোর্ট';

    public ?array $data = [];

    public array $summary = [];

    public array $allTeachersReport = [];

    public function mount(): void
    {
        $this->form->fill([
            'teacher_id' => 'all',
            'month' => now()->format('Y-m'),
        ]);

        $this->loadReport();
    }

    public function form(Form $form): Form
    {
        $teacherOptions = ['all' => 'সকল শিক্ষক'] + User::where('role', 'teacher')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->label('শিক্ষক নির্বাচন করুন')
                    ->options($teacherOptions)
                    ->default('all')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadReport()),

                Forms\Components\Select::make('month')
                    ->label('মাস নির্বাচন করুন')
                    ->options(function () {
                        $months = [];
                        $monthNames = [
                            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
                            5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
                            9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
                        ];

                        for ($year = now()->subYear()->year; $year <= now()->year; $year++) {
                            for ($m = 1; $m <= 12; $m++) {
                                $key = sprintf('%d-%02d', $year, $m);
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
            ->columns(2)
            ->statePath('data');
    }

    private function getBanglaDayName(string $date): string
    {
        $days = [
            'Saturday' => 'শনিবার',
            'Sunday' => 'রবিবার',
            'Monday' => 'সোমবার',
            'Tuesday' => 'মঙ্গলবার',
            'Wednesday' => 'বুধবার',
            'Thursday' => 'বৃহস্পতিবার',
            'Friday' => 'শুক্রবার',
        ];

        $englishDay = Carbon::parse($date)->format('l');

        return $days[$englishDay] ?? $englishDay;
    }

    private function formatMinutesAsHours(int $minutes): string
    {
        if ($minutes <= 0) {
            return '০ ঘণ্টা (০ মিনিট)';
        }

        $hours = round($minutes / 60, 2);

        return "{$hours} ঘণ্টা ({$minutes} মিনিট)";
    }

    private function calculatePerformance(int $present, int $late, int $absent, int $lateMinutes): array
    {
        $totalDays = $present + $late + $absent;

        if ($totalDays === 0) {
            return [
                'score' => 0,
                'remark' => 'কোনো হাজিরা ডাটা নেই',
                'badge_color' => 'gray',
            ];
        }

        // উপস্থিতি (present + late) অংশের ওয়েট ৭৫%, সময়ানুবর্তিতা (লেট না করা) অংশের ওয়েট ২৫%
        $attendedDays = $present + $late;
        $attendanceRatio = $attendedDays / $totalDays;

        $punctualityPenalty = min(1, ($late * 0.04) + ($lateMinutes / 600));
        $punctualityRatio = max(0, 1 - $punctualityPenalty);

        $score = round((($attendanceRatio * 75) + ($punctualityRatio * 25)), 1);
        $score = max(0, min(100, $score));

        if ($score >= 90) {
            return [
                'score' => $score,
                'remark' => 'অসাধারণ ও অত্যন্ত নিয়মিত',
                'badge_color' => 'green',
            ];
        }

        if ($score >= 75) {
            return [
                'score' => $score,
                'remark' => 'সন্তোষজনক পারফরম্যান্স',
                'badge_color' => 'blue',
            ];
        }

        if ($score >= 60) {
            return [
                'score' => $score,
                'remark' => 'উন্নতি প্রয়োজন (লেট ও অনুপস্থিতি কমানো দরকার)',
                'badge_color' => 'amber',
            ];
        }

        return [
            'score' => $score,
            'remark' => 'অনিয়মিত — কর্তৃপক্ষের দৃষ্টি আকর্ষণ',
            'badge_color' => 'red',
        ];
    }

    public function loadReport(): void
    {
        $formData = $this->form->getState();
        $selectedTeacher = $formData['teacher_id'] ?? 'all';
        $selectedMonth = $formData['month'] ?? now()->format('Y-m');

        $parts = explode('-', $selectedMonth);
        $year = (int) ($parts[0] ?? now()->year);
        $month = (int) ($parts[1] ?? now()->month);

        $this->summary = [];
        $this->allTeachersReport = [];

        if ($selectedTeacher === 'all' || empty($selectedTeacher)) {
            $teachers = User::where('role', 'teacher')->orderBy('name')->get();

            foreach ($teachers as $teacher) {
                $attendances = TeacherAttendance::where('user_id', $teacher->id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->orderBy('date', 'asc')
                    ->get();

                $totalPresent = $attendances->where('status', 'present')->count();
                $totalLate = $attendances->where('status', 'late')->count();
                $totalAbsent = $attendances->where('status', 'absent')->count();
                $totalHours = round((float) $attendances->sum('total_hours'), 2);
                $totalLatenessMinutes = (int) $attendances->sum('lateness_minutes');

                $perf = $this->calculatePerformance(
                    $totalPresent,
                    $totalLate,
                    $totalAbsent,
                    $totalLatenessMinutes
                );

                $this->allTeachersReport[] = [
                    'teacher_id' => $teacher->id,
                    'name' => $teacher->name,
                    'total_present' => $totalPresent,
                    'total_late' => $totalLate,
                    'total_absent' => $totalAbsent,
                    'total_hours' => $totalHours,
                    'total_lateness_minutes' => $totalLatenessMinutes,
                    'late_in_hours_text' => $this->formatMinutesAsHours($totalLatenessMinutes),
                    'performance_score' => $perf['score'],
                    'remark' => $perf['remark'],
                    'badge_color' => $perf['badge_color'],
                ];
            }

            return;
        }

        $attendances = TeacherAttendance::where('user_id', (int) $selectedTeacher)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($record) {
                $record->bangla_day = $this->getBanglaDayName($record->date);
                return $record;
            });

        $totalPresent = $attendances->where('status', 'present')->count();
        $totalLate = $attendances->where('status', 'late')->count();
        $totalAbsent = $attendances->where('status', 'absent')->count();
        $totalHours = round((float) $attendances->sum('total_hours'), 2);
        $totalLatenessMinutes = (int) $attendances->sum('lateness_minutes');
        $perf = $this->calculatePerformance($totalPresent, $totalLate, $totalAbsent, $totalLatenessMinutes);

        $this->summary = [
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_absent' => $totalAbsent,
            'total_hours' => $totalHours,
            'total_lateness_minutes' => $totalLatenessMinutes,
            'late_in_hours_text' => $this->formatMinutesAsHours($totalLatenessMinutes),
            'performance_score' => $perf['score'],
            'remark' => $perf['remark'],
            'badge_color' => $perf['badge_color'],
            'records' => $attendances,
        ];
    }
}