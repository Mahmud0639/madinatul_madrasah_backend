<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'basic_salary',
        'house_rent',
        'other_allowance',
        'effective_from',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'house_rent' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'effective_from' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getGrossSalaryAttribute(): float
    {
        return (float) $this->basic_salary
            + (float) $this->house_rent
            + (float) $this->other_allowance;
    }
}