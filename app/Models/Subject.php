<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['madrasah_class_id', 'subject_name', 'code'];

    // madrasah_classes টেবিলের সাথে রিলেশন
    public function madrasahClass()
    {
        return $this->belongsTo(MadrasahClass::class, 'madrasah_class_id');
    }
}