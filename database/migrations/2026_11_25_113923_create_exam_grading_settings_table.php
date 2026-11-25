<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_grading_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('ডিফল্ট গ্রেডিং নিয়ম');
            $table->string('grading_mode', 30)->default('madrasah'); // madrasah, school, custom
            $table->boolean('include_oral')->default(true); // মৌখিক নম্বর যুক্ত হবে কি না
            $table->boolean('fail_on_any_subject')->default(true); // এক বিষয়ে ফেল করলে সামগ্রিক ফেল/রাসিব
            $table->json('grade_ranges')->nullable(); // গ্রেডের নাম, min%, max%, gpa, active
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_grading_settings');
    }
};