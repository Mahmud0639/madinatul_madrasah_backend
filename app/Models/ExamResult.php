<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamResult extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_name',
        'subject_name',
        'written_marks',
        'oral_marks',
        'total_marks',
        'grade',
    ];
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
