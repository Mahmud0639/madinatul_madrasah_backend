<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'lesson_type',
        'body_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}