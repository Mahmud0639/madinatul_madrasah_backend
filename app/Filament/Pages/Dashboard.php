<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MadrasahQuickActions;
use App\Filament\Widgets\MadrasahStatsOverview;
use App\Filament\Widgets\StudentsByDepartmentChart;
use App\Filament\Widgets\TeacherAttendanceChart;
use App\Filament\Widgets\TeacherPerformanceBarChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.dashboard';

    public string $activeTab = 'all';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getTopWidgets(): array
    {
        return [
            MadrasahQuickActions::class,
            MadrasahStatsOverview::class,
        ];
    }

    public function getTabWidgets(): array
    {
        if ($this->activeTab === 'attendance') {
            return [
                TeacherAttendanceChart::class,
                TeacherPerformanceBarChart::class,
            ];
        }

        if ($this->activeTab === 'students') {
            return [
                StudentsByDepartmentChart::class,
            ];
        }

        return [
            TeacherAttendanceChart::class,
            TeacherPerformanceBarChart::class,
            StudentsByDepartmentChart::class,
        ];
    }
}