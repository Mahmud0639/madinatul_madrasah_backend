<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // ইউজার বা শিক্ষকের আইডি
            $table->date('date'); // হাজিরার তারিখ
            $table->time('in_time')->nullable(); // প্রবেশের সময়
            $table->time('out_time')->nullable(); // প্রস্থানের সময়
            $table->enum('status', ['present', 'absent', 'late'])->default('present'); // স্ট্যাটাস
            $table->decimal('total_hours', 5, 2)->default(0); // মোট কাজের ঘণ্টা
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};