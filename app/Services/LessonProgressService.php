<?php

namespace App\Services;

use App\Models\LessonStudentStatus;
use Carbon\Carbon;

class LessonProgressService
{
    /**
     * নির্দিষ্ট ছাত্রের গত $days দিনের (যেমন: 7 বা 30) পড়া অগ্রগতি সামারি
     */
    public function getStudentSummary(int $studentId, int $days = 7): array
    {
        $since = Carbon::now('Asia/Dhaka')->subDays($days)->startOfDay();

        $statuses = LessonStudentStatus::query()
            ->where('student_id', $studentId)
            ->whereHas('lesson', fn ($q) => $q->whereDate('lesson_date', '>=', $since->toDateString()))
            ->with('lesson')
            ->get();

        $total = $statuses->count();
        $completed = $statuses->whereNotNull('completed_at')->count();
        $verifiedGood = $statuses->where('teacher_status', 'completed_good')->count();
        $needsRevision = $statuses->where('teacher_status', 'partial_revision')->count();

        $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        // বিষয়ভিত্তিক ব্রেকডাউন
        $bySubject = $statuses->groupBy(function ($st) {
            return $st->lesson?->subject_name ?? ($st->lesson?->subject->subject_name ?? 'সাধারণ');
        })->map(function ($group, $subject) {
            $subTotal = $group->count();
            $subDone = $group->whereNotNull('completed_at')->count();
            return [
                'subject' => $subject,
                'total' => $subTotal,
                'completed' => $subDone,
                'completion_rate' => $subTotal > 0 ? round(($subDone / $subTotal) * 100, 1) : 0.0,
            ];
        })->values();

        return [
            'period_days' => $days,
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'pending_lessons' => max(0, $total - $completed),
            'completion_rate' => $rate,
            'verified_good_count' => $verifiedGood,
            'needs_revision_count' => $needsRevision,
            'by_subject' => $bySubject,
        ];
    }
}