<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table = 'class_rooms';
    protected $fillable = ['name', 'description', 'sort_order'];

    // একটি ক্লাসের অধীনে অনেকগুলো সাবজেক্ট থাকতে পারে
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_room_id');
    }
}