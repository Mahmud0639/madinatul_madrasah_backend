<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use App\Models\Student;
use Filament\Widgets\ChartWidget;

class StudentsByDepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'বিভাগভিত্তিক শিক্ষার্থী বণ্টন';

    protected static ?int $sort = 4;

    public function getDescription(): ?string
    {
        $totalStudents = Student::count();

        return "মাদ্রাসার মোট {$totalStudents}জন শিক্ষার্থীর বিভাগভিত্তিক হিসাব";
    }

    protected function getData(): array
    {
        $departments = Department::query()
            ->withCount('students')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($departments as $department) {
            $name = $department->name ?? $department->title ?? ("বিভাগ #" . $department->id);
            $count = (int) ($department->students_count ?? 0);

            $labels[] = "{$name} ({$count} জন)";
            $counts[] = $count;
        }

        $unassignedCount = Student::query()
            ->whereNull('department_id')
            ->count();

        if ($unassignedCount > 0) {
            $labels[] = "বিভাগ নির্ধারিত নয় ({$unassignedCount} জন)";
            $counts[] = $unassignedCount;
        }

        if (empty($counts)) {
            $labels = ['কোনো শিক্ষার্থী নেই (0 জন)'];
            $counts = [0];
        }

        $palette = [
            '#059669',
            '#0284c7',
            '#d97706',
            '#7c3aed',
            '#db2777',
            '#0d9488',
        ];

        $colors = [];
        foreach ($counts as $index => $_) {
            $colors[] = $palette[$index % count($palette)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'শিক্ষার্থী সংখ্যা',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}