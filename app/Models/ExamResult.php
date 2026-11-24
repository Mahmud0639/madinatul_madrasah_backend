<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_name',
        'subject_name',
        'written_marks',
        'oral_marks',
        'full_marks',
        'pass_marks',
        'total_marks',
        'percentage',
        'grade',
        'gpa',
        'result_status',
        'remarks',
    ];

    protected $casts = [
        'written_marks' => 'float',
        'oral_marks' => 'float',
        'full_marks' => 'float',
        'pass_marks' => 'float',
        'total_marks' => 'float',
        'percentage' => 'float',
        'gpa' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (ExamResult $result) {
            $written = (float) ($result->written_marks ?? 0);
            $oral = (float) ($result->oral_marks ?? 0);
            $fullMarks = (float) ($result->full_marks ?: 100);
            $passMarks = (float) ($result->pass_marks ?: 33);

            $total = round($written + $oral, 2);
            $percentage = $fullMarks > 0 ? round(($total / $fullMarks) * 100, 2) : 0;

            [$grade, $gpa, $status] = self::evaluateGrade($total, $percentage, $passMarks);

            $result->total_marks = $total;
            $result->percentage = $percentage;
            $result->grade = $result->grade ?: $grade;
            $result->gpa = $gpa;
            $result->result_status = $status;
        });
    }

    /**
     * শতকরা ও পাস নম্বর অনুযায়ী গ্রেড, GPA এবং ফলাফল স্ট্যাটাস নির্ণয়
     *
     * @return array{0: string, 1: float, 2: string}
     */
    public static function evaluateGrade(float $total, float $percentage, float $passMarks): array
    {
        if ($total < $passMarks || $percentage < 33) {
            return ['F', 0.00, 'failed'];
        }

        return match (true) {
            $percentage >= 80 => ['A+', 5.00, 'passed'],
            $percentage >= 70 => ['A', 4.00, 'passed'],
            $percentage >= 60 => ['A-', 3.50, 'passed'],
            $percentage >= 50 => ['B', 3.00, 'passed'],
            $percentage >= 40 => ['C', 2.00, 'passed'],
            default => ['D', 1.00, 'passed'],
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}