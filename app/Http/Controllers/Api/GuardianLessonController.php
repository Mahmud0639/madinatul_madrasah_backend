<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyLesson;
use App\Models\LessonStudentStatus;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardianLessonController extends Controller
{
    /**
     * গার্ডিয়ানের অধীনে থাকা সন্তানদের তালিকা (Multi-Child Switcher-এর জন্য)
     */
    public function children(Request $request): JsonResponse
    {
        $user = $request->user();

        $children = Student::where('parent_id', $user->id)
            ->with('department')
            ->get()
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'roll_number' => $student->roll_number,
                    'jamatu_class' => $student->jamatu_class,
                    'department' => $student->department->name ?? null,
                    'photo_url' => $student->photo ? asset('storage/' . $student->photo) : null,
                ];
            });

        return response()->json([
            'status' => true,
            'children' => $children,
        ]);
    }

    /**
     * নির্দিষ্ট সন্তানের পড়া তালিকা
     * tab: today | upcoming_pending | completed | all
     */
    public function index(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();

        $student = Student::where('id', $studentId)
            ->where('parent_id', $user->id)
            ->firstOrFail();

        $tab = $request->get('tab', 'today');
        $today = Carbon::now('Asia/Dhaka')->toDateString();

        $query = LessonStudentStatus::query()
            ->where('student_id', $student->id)
            ->with(['lesson.madrasahClass', 'lesson.subject', 'lesson.teacher']);

        if ($tab === 'today') {
            $query->whereNull('completed_at')
                ->whereHas('lesson', function ($q) use ($today) {
                    $q->whereDate('lesson_date', $today)
                        ->orWhereDate('due_date', $today);
                });
        } elseif ($tab === 'upcoming_pending') {
            $query->whereNull('completed_at')
                ->whereHas('lesson', function ($q) use ($today) {
                    $q->whereDate('lesson_date', '!=', $today)
                        ->where(function ($sub) use ($today) {
                            $sub->whereNull('due_date')
                                ->orWhereDate('due_date', '>=', $today);
                        });
                });
        } elseif ($tab === 'completed') {
            $query->whereNotNull('completed_at');
        }

        $statuses = $query->latest('id')->get();

        // ট্যাব কাউন্ট (ব্যাচ/ট্যাবে দেখানোর জন্য)
        $todayCount = LessonStudentStatus::where('student_id', $student->id)
            ->whereNull('completed_at')
            ->whereHas('lesson', fn ($q) => $q->whereDate('lesson_date', $today)->orWhereDate('due_date', $today))
            ->count();

        $pendingCount = LessonStudentStatus::where('student_id', $student->id)
            ->whereNull('completed_at')
            ->count();

        $completedCount = LessonStudentStatus::where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->count();

        $items = $statuses->map(function (LessonStudentStatus $st) {
            $lesson = $st->lesson;
            $statusLabels = LessonStudentStatus::teacherStatusLabels();

            return [
                'status_id' => $st->id,
                'lesson_id' => $lesson?->id,
                'lesson_type' => $lesson?->lesson_type,
                'lesson_type_label' => DailyLesson::typeLabels()[$lesson?->lesson_type] ?? $lesson?->lesson_type,
                'subject_name' => $lesson?->subject_name ?? ($lesson?->subject->subject_name ?? 'সাধারণ'),
                'class_name' => $lesson?->madrasahClass->name ?? null,
                'teacher_name' => $lesson?->teacher->name ?? null,
                'title' => $lesson?->title,
                'description' => $lesson?->description,
                'lesson_date' => $lesson?->lesson_date?->format('Y-m-d'),
                'due_date' => $lesson?->due_date?->format('Y-m-d'),
                'is_targeted' => (bool) $st->is_targeted,
                'is_seen' => !is_null($st->seen_at),
                'seen_at' => $st->seen_at?->timezone('Asia/Dhaka')->format('Y-m-d h:i A'),
                'is_completed' => !is_null($st->completed_at),
                'completed_at' => $st->completed_at?->timezone('Asia/Dhaka')->format('Y-m-d h:i A'),
                'guardian_note' => $st->guardian_note,
                'teacher_status' => $st->teacher_status,
                'teacher_status_label' => $statusLabels[$st->teacher_status] ?? $st->teacher_status,
                'teacher_remark' => $st->teacher_remark,
                'verified_at' => $st->verified_at?->timezone('Asia/Dhaka')->format('Y-m-d h:i A'),
            ];
        });

        return response()->json([
            'status' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'jamatu_class' => $student->jamatu_class,
            ],
            'counts' => [
                'today' => $todayCount,
                'pending' => $pendingCount,
                'completed' => $completedCount,
            ],
            'lessons' => $items,
        ]);
    }

    /**
     * গার্ডিয়ান পড়া ওপেন করলে Seen মার্ক করা
     */
    public function markSeen(Request $request, int $studentId, int $lessonId): JsonResponse
    {
        $student = Student::where('id', $studentId)
            ->where('parent_id', $request->user()->id)
            ->firstOrFail();

        $status = LessonStudentStatus::where('student_id', $student->id)
            ->where('daily_lesson_id', $lessonId)
            ->firstOrFail();

        if (is_null($status->seen_at)) {
            $status->update([
                'seen_at' => Carbon::now('Asia/Dhaka'),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'পড়া দেখা হয়েছে বলে মার্ক করা হয়েছে।',
            'seen_at' => $status->fresh()->seen_at?->format('Y-m-d h:i A'),
        ]);
    }

    /**
     * গার্ডিয়ান পড়া সম্পন্ন মার্ক করা + ঐচ্ছিক নোট যোগ করা
     */
    public function markCompleted(Request $request, int $studentId, int $lessonId): JsonResponse
    {
        $request->validate([
            'completed' => ['nullable', 'boolean'],
            'guardian_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $student = Student::where('id', $studentId)
            ->where('parent_id', $request->user()->id)
            ->firstOrFail();

        $status = LessonStudentStatus::where('student_id', $student->id)
            ->where('daily_lesson_id', $lessonId)
            ->firstOrFail();

        $completed = $request->boolean('completed', true);
        $now = Carbon::now('Asia/Dhaka');

        $status->update([
            'seen_at' => $status->seen_at ?? $now,
            'completed_at' => $completed ? ($status->completed_at ?? $now) : null,
            'guardian_note' => $request->has('guardian_note') ? $request->input('guardian_note') : $status->guardian_note,
        ]);

        return response()->json([
            'status' => true,
            'message' => $completed ? 'পড়া সম্পন্ন হিসেবে সংরক্ষণ করা হয়েছে।' : 'পড়া অসম্পন্ন হিসেবে আপডেট করা হয়েছে।',
            'data' => [
                'is_completed' => !is_null($status->fresh()->completed_at),
                'completed_at' => $status->fresh()->completed_at?->format('Y-m-d h:i A'),
                'guardian_note' => $status->fresh()->guardian_note,
            ],
        ]);
    }
}