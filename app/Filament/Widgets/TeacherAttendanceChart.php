<?php

namespace App\Filament\Widgets;

use App\Models\TeacherAttendance;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class TeacherAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'শিক্ষকের মাসিক উপস্থিতির চার্ট';

    protected static ?string $maxHeight = '260px';

    public ?int $teacherId = null;

    public ?string $month = null;

    public function getDescription(): ?string
    {
        $selectedMonth = $this->month ?? now()->format('Y-m');
        $parts = explode('-', $selectedMonth);
        $year = (int) ($parts[0] ?? now()->year);
        $month = (int) ($parts[1] ?? now()->month);

        $monthNames = [
            1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
            5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
        ];

        $monthLabel = ($monthNames[$month] ?? $month) . ' ' . $year;

        $query = TeacherAttendance::whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($this->teacherId) {
            $query->where('user_id', $this->teacherId);
            $teacherName = User::find($this->teacherId)?->name ?? 'নির্বাচিত শিক্ষক';
            $count = $query->count();

            return "{$teacherName} — {$monthLabel} মাসের মোট {$count}টি হাজিরা এন্ট্রির হিসাব";
        }

        $count = $query->count();

        return "সকল শিক্ষকের {$monthLabel} মাসের মোট {$count}টি হাজিরা এন্ট্রির হিসাব";
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $selectedMonth = $this->month ?? now()->format('Y-m');
        $parts = explode('-', $selectedMonth);
        $year = (int) ($parts[0] ?? now()->year);
        $month = (int) ($parts[1] ?? now()->month);

        $query = TeacherAttendance::whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($this->teacherId) {
            $query->where('user_id', $this->teacherId);
        }

        $attendances = $query->get();

        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();

        return [
            'datasets' => [
                [
                    'data' => [$present, $late, $absent],
                    'backgroundColor' => ['#10b981', '#f59e0b', '#ef4444'],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                "উপস্থিত: {$present}টি",
                "বিলম্ব: {$late}টি",
                "অনুপস্থিত: {$absent}টি",
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}