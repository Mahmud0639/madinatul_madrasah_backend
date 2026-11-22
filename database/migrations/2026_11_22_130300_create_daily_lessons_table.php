<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('madrasah_class_id')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('subject_name')->nullable(); // স্ন্যাপশট বা ডাইরেক্ট নাম রাখার সুবিধার্থে
            $table->unsignedBigInteger('teacher_id')->nullable()->index();

            $table->string('lesson_type')->default('sabak'); // sabak, homework, revision, sheet
            $table->string('title');
            $table->longText('description');

            $table->date('lesson_date')->index(); // পড়া দেওয়ার তারিখ
            $table->date('due_date')->nullable()->index(); // শুনানো বা জমা দেওয়ার সময়সীমা

            // 'all_class' = পুরো ক্লাস, 'specific_students' = নির্দিষ্ট কয়েকজন ছাত্র
            $table->string('target_type')->default('all_class');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_lessons');
    }
};