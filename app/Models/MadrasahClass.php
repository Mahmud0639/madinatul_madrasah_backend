<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MadrasahClass extends Model
{
    //
    protected $fillable = ['name', 'department_id'];
   
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'madrasah_class_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
