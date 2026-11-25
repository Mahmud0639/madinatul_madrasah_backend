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
            $setting = ExamGradingSetting::activeSetting();
            $includeOral = (bool) ($setting->include_oral ?? true);

            $written = (float) ($result->written_marks ?? 0);
            $oral = $includeOral ? (float) ($result->oral_marks ?? 0) : 0.0;
            $fullMarks = (float) ($result->full_marks ?: 100);
            $passMarks = (float) ($result->pass_marks ?: 33);

            $result->oral_marks = round($oral, 2);

            if (($result->result_status ?? 'passed') === 'absent') {
                $result->total_marks = 0;
                $result->percentage = 0;
                $result->grade = 'অনুপস্থিত';
                $result->gpa = 0.00;
                return;
            }

            $total = round($written + $oral, 2);
            $percentage = $fullMarks > 0 ? round(($total / $fullMarks) * 100, 2) : 0;

            [$grade, $gpa, $status] = $setting->evaluateByPercentage($total, $percentage, $passMarks);

            $result->total_marks = $total;
            $result->percentage = $percentage;
            $result->grade = $grade;
            $result->gpa = $gpa;
            $result->result_status = $status;
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}