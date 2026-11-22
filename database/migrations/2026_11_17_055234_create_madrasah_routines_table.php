<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('madrasah_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('madrasah_class_id')->constrained('madrasah_classes')->cascadeOnDelete(); // কোন ক্লাসের রুটিন
            $table->string('day_name'); // বার বা দিন (যেমন: শনিবার, রবিবার ইত্যাদি)
            $table->string('period_name'); // পিরিয়ডের নাম (যেমন: ১ম পিরিয়ড)
            $table->string('time_slot'); // সময় (যেমন: ০৭:০০ - ০৭:৫০)
            $table->string('subject_name'); // বিষয় বা কিতাবের নাম
            $table->string('teacher_name')->nullable(); // উস্তাদ বা শিক্ষকের নাম
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('madrasah_routines');
    }
};