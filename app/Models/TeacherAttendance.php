<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TeacherAttendance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::saving(function ($attendance) {
            // ১. লেট এবং ল্যাটনেস মিনিট হিসাব
            if ($attendance->in_time) {
                $officeStartTimeString = Cache::get('office_start_time', '08:00:00');
                $graceMinutes = (int) Cache::get('office_grace_minutes', 15);

                $officialStartTime = Carbon::parse($officeStartTimeString);
                $inTime = Carbon::parse($attendance->in_time);
                $allowedTime = (clone $officialStartTime)->addMinutes($graceMinutes);

                if ($inTime->greaterThan($allowedTime)) {
                    $attendance->status = 'late';
                    $attendance->lateness_minutes = $officialStartTime->diffInMinutes($inTime);
                } else {
                    $attendance->status = 'present';
                    $attendance->lateness_minutes = 0;
                }
            }

            // ২. মোট কর্মঘণ্টা (Total Hours) হিসাব
            if ($attendance->in_time && $attendance->out_time) {
                $in = Carbon::parse($attendance->in_time);
                $out = Carbon::parse($attendance->out_time);
                
                // যদি আউট-টাইম ইন-টাইমের পরের হয়, তবে ঘণ্টা হিসাব করবে
                if ($out->greaterThan($in)) {
                    $attendance->total_hours = round($in->diffInMinutes($out) / 60, 2);
                } else {
                    $attendance->total_hours = 0;
                }
            }
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}