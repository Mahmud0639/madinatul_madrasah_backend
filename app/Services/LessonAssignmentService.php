<?php

namespace App\Services;

use App\Models\DailyLesson;
use App\Models\LessonStudentStatus;
use App\Models\MadrasahClass;
use App\Models\Student;

class LessonAssignmentService
{
    public function syncStudentStatuses(DailyLesson $lesson, array $selectedStudentIds = []): void
    {
        if ($lesson->target_type === 'specific_students') {
            $targetIds = collect($selectedStudentIds)->map(fn ($id) => (int) $id)->unique()->values();

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

        $studentQuery = Student::query();

        if (!empty($lesson->department_id)) {
            $studentQuery->where('department_id', $lesson->department_id);
        }

        if (!empty($lesson->madrasah_class_id)) {
            $className = MadrasahClass::find($lesson->madrasah_class_id)?->name;
            if ($className) {
                $studentQuery->where('jamatu_class', $className);
            }
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