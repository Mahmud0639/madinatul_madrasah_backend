<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonStudentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_lesson_id',
        'student_id',
        'is_targeted',
        'seen_at',
        'completed_at',
        'guardian_note',
        'teacher_status',
        'teacher_remark',
        'verified_at',
    ];

    protected $casts = [
        'is_targeted' => 'boolean',
        'seen_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(DailyLesson::class, 'daily_lesson_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public static function teacherStatusLabels(): array
    {
        return [
            'pending' => 'অপেক্ষমাণ (Pending)',
            'completed_good' => 'সম্পন্ন / চমৎকার হয়েছে',
            'partial_revision' => 'আংশিক / আবার দেখতে হবে',
            'not_done' => 'পড়া হয়নি',
        ];
    }
}