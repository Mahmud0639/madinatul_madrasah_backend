<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'madrasah_class_id',
        'subject_id',
        'subject_name',
        'teacher_id',
        'lesson_type',
        'title',
        'description',
        'lesson_date',
        'due_date',
        'target_type',
    ];

    protected $casts = [
        'lesson_date' => 'date',
        'due_date' => 'date',
    ];

    public function madrasahClass(): BelongsTo
    {
        return $this->belongsTo(MadrasahClass::class, 'madrasah_class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function studentStatuses(): HasMany
    {
        return $this->hasMany(LessonStudentStatus::class, 'daily_lesson_id');
    }

    public static function typeLabels(): array
    {
        return [
            'sabak' => 'সবক / নাজেরা / হিফজ',
            'homework' => 'হোমওয়ার্ক / বাড়ির কাজ',
            'revision' => 'পুনঃপড়া (Revision)',
            'sheet' => 'শিট / নোট পড়া',
        ];
    }
}