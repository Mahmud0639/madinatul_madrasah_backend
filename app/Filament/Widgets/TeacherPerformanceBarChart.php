<?php

namespace App\Filament\Widgets;

use App\Models\TeacherAttendance;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class TeacherPerformanceBarChart extends ChartWidget
{
    protected static bool $isDiscovered = false;
    protected static ?string $heading = 'শিক্ষকদের মাসিক উপস্থিতি ও বিলম্বের তুলনা';

    protected static ?string $description = 'নির্বাচিত মাসের শিক্ষকভিত্তিক উপস্থিত ও বিলম্ব দিনের হিসাব';

    public ?string $teacherId = 'all';

    public ?string $month = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $selectedMonth = $this->month ?? now()->format('Y-m');
        $parts = explode('-', $selectedMonth);
        $year = (int) ($parts[0] ?? now()->year);
        $month = (int) ($parts[1] ?? now()->month);

        $query = User::where('role', 'teacher')->orderBy('name');

        if (!empty($this->teacherId) && $this->teacherId !== 'all') {
            $query->where('id', (int) $this->teacherId);
        }

        $teachers = $query->get();

        $labels = [];
        $presentCounts = [];
        $lateCounts = [];

        foreach ($teachers as $teacher) {
            $attendances = TeacherAttendance::where('user_id', $teacher->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();

            $labels[] = $teacher->name;
            $presentCounts[] = $attendances->where('status', 'present')->count();
            $lateCounts[] = $attendances->where('status', 'late')->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'উপস্থিত (দিন)',
                    'data' => $presentCounts,
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'বিলম্ব / লেট (দিন)',
                    'data' => $lateCounts,
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}