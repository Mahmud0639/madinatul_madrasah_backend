<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'rfid_card_number',
        'roll_number',
        'jamatu_class',
        'section',
        'date_of_birth',
    ];

    // অভিভাবক (User) এর সাথে রিলেশন
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }
}