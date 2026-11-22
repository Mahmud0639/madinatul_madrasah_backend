<?php

namespace App\Services;

use App\Models\DailyLesson;
use App\Models\LessonStudentStatus;
use App\Models\Student;

class LessonAssignmentService
{
    public function syncStudentStatuses(DailyLesson $lesson, array $selectedStudentIds = []): void
    {
        if ($lesson->target_type === 'specific_students') {
            $targetIds = collect($selectedStudentIds)->map(fn ($id) => (int) $id)->unique()->values();

            // যেসব ছাত্রকে আর রাখা হয়নি তাদের স্ট্যাটাস মুছে ফেলা
            LessonStudentStatus::where('daily_lesson_id', $lesson->id)
                ->whereNotIn('student_id', $targetIds)
                ->delete();

            foreach ($targetIds as $studentId) {
                LessonStudentStatus::updateOrCreate(
                    [
                        'daily_lesson_id' => $lesson->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'is_targeted' => true,
                    ]
                );
            }

            return;
        }

        // 'all_class' হলে ওই ক্লাসের সব ছাত্রের জন্য স্ট্যাটাস নিশ্চিত করা
        $studentQuery = Student::query()->where('madrasah_class_id', $lesson->madrasah_class_id);

        if (!empty($lesson->department_id)) {
            $studentQuery->where('department_id', $lesson->department_id);
        }

        $studentIds = $studentQuery->pluck('id');

        LessonStudentStatus::where('daily_lesson_id', $lesson->id)
            ->whereNotIn('student_id', $studentIds)
            ->delete();

        foreach ($studentIds as $studentId) {
            LessonStudentStatus::firstOrCreate(
                [
                    'daily_lesson_id' => $lesson->id,
                    'student_id' => $studentId,
                ],
                [
                    'is_targeted' => false,
                    'teacher_status' => 'pending',
                ]
            );
        }
    }
}