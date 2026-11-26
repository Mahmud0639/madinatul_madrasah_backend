<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Fee;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class MadrasahStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalStudents = class_exists(Student::class) ? Student::count() : 0;

        $today = now()->toDateString();
        $todayAttendanceTotal = 0;
        $todayPresent = 0;

        if (class_exists(Attendance::class) && Schema::hasTable('attendances')) {
            $query = Attendance::query();
            if (Schema::hasColumn('attendances', 'date')) {
                $query->whereDate('date', $today);
            } else {
                $query->whereDate('created_at', $today);
            }

            $todayRows = $query->get();
            $todayAttendanceTotal = $todayRows->count();
            $todayPresent = $todayRows->where('status', 'present')->count();
        }

        $attendanceRate = $todayAttendanceTotal > 0
            ? round(($todayPresent / $todayAttendanceTotal) * 100, 1) . '%'
            : '০%';

        $totalPaid = 0;
        $totalDue = 0;

        if (class_exists(Fee::class) && Schema::hasTable('fees')) {
            $fees = Fee::all();
            $totalPaid = round((float) $fees->sum('paid_amount'), 2);
            $totalDue = round((float) $fees->sum(fn ($f) => max(0, (float) $f->amount - (float) $f->paid_amount)), 2);
        }

        $totalExamEntries = class_exists(ExamResult::class) ? ExamResult::count() : 0;

        return [
            Stat::make('মোট শিক্ষার্থী', number_format($totalStudents) . ' জন')
                ->description('মাদ্রাসায় নিবন্ধিত মোট ছাত্র-ছাত্রী')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('emerald'),

            Stat::make('আজকের উপস্থিতি', $attendanceRate)
                ->description("উপস্থিত {$todayPresent} / {$todayAttendanceTotal} জন")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('মোট বকেয়া ফি (Due)', '৳' . number_format($totalDue))
                ->description('মোট জমা হয়েছে: ৳' . number_format($totalPaid))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalDue > 0 ? 'danger' : 'success'),

            Stat::make('পরীক্ষার রেজাল্ট এন্ট্রি', number_format($totalExamEntries) . ' টি')
                ->description('বিষয়ভিত্তিক ফলাফল রেকর্ড')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),
        ];
    }
}