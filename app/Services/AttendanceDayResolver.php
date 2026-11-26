<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\TeacherAttendance;
use App\Models\TeacherLeave;
use Carbon\Carbon;

class AttendanceDayResolver
{
    /**
     * নির্দিষ্ট তারিখ ও শিক্ষকের স্ট্যাটাস নির্ণয় করে:
     * present | late | on_leave | holiday | absent
     */
    public function resolveStatus(int $userId, string|Carbon $date, ?TeacherAttendance $attendance = null): string
    {
        $targetDate = Carbon::parse($date)->toDateString();

        $record = $attendance ?? TeacherAttendance::query()
            ->where('user_id', $userId)
            ->whereDate('date', $targetDate)
            ->first();

        if ($record && in_array($record->status, ['present', 'late'], true)) {
            return $record->status;
        }

        if (TeacherLeave::hasApprovedLeave($userId, $targetDate)) {
            return 'on_leave';
        }

        if (Holiday::isHolidayDate($targetDate)) {
            return 'holiday';
        }

        return 'absent';
    }
}