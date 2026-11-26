<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Student;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'description'];

    public function madrasahClasses(): HasMany
    {
        return $this->hasMany(MadrasahClass::class);
    }

    public function students(): HasMany
{
    return $this->hasMany(Student::class, 'department_id');
}
}