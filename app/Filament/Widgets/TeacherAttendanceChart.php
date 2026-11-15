<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\TeacherAttendance;
use Livewire\Attributes\Reactive;

class TeacherAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'শিক্ষকের মাসিক উপস্থিতির পাই চার্ট';
    
    #[Reactive]
    public ?int $teacherId = null;

    #[Reactive]
    public ?string $month = null;

    protected function getData(): array
    {
        if (!$this->teacherId) {
            return [
                'datasets' => [
                    [
                        'data' => [0, 0, 0],
                        'backgroundColor' => ['#10B981', '#F59E0B', '#EF4444'],
                    ],
                ],
                'labels' => ['উপস্থিত', 'বিলম্ব (লেট)', 'অনুপস্থিত'],
            ];
        }

        $selectedMonth = $this->month ?? now()->format('Y-m');
        $parts = explode('-', $selectedMonth);
        $year = $parts[0] ?? now()->year;
        $month = $parts[1] ?? now()->month;

        $attendances = TeacherAttendance::where('user_id', $this->teacherId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $present = $attendances->where('status', 'present')->count();
        $late = $attendances->where('status', 'late')->count();
        $absent = $attendances->where('status', 'absent')->count();

        return [
            'datasets' => [
                [
                    'data' => [$present, $late, $absent],
                    'backgroundColor' => ['#10B981', '#F59E0B', '#EF4444'],
                ],
            ],
            'labels' => ['উপস্থিত (Present)', 'বিলম্ব (Late)', 'অনুপস্থিত (Absent)'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}