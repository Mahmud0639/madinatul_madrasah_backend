<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fee extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_title',
        'amount',
        'paid_amount',
        'status',
        'due_date',
    ];
    // স্টুডেন্ট টেবিলের সাথে রিলেশন
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
