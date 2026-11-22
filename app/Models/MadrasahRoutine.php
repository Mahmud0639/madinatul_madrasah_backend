<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class  MadrasahRoutine extends Model
{
    use HasFactory;

    protected $fillable = [
    'madrasah_class_id',
    'day_name',
    'period_name',
    'start_time',
    'end_time',
    'subject_name',
    'teacher_name',
];

    public function madrasahClass(): BelongsTo
    {
        return $this->belongsTo(MadrasahClass::class, 'madrasah_class_id');
    }
    public function routines() { return $this->hasMany(MadrasahRoutine::class); }
}
