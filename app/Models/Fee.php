<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_title',
        'amount',
        'paid_amount',
        'status',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_amount' => 'float',
        'due_date' => 'date',
    ];

    /**
     * প্রতিটি ফি-এর বকেয়া পরিমাণ (Due)
     */
    public function getDueAmountAttribute(): float
    {
        $amount = (float) ($this->amount ?? 0);
        $paid = (float) ($this->paid_amount ?? 0);

        return round(max(0, $amount - $paid), 2);
    }

    // স্টুডেন্ট টেবিলের সাথে রিলেশন
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}