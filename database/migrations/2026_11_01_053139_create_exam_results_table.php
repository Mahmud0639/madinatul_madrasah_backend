<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('exam_name'); // যেমন: ১ম সাময়িক পরীক্ষা ২০২৬
            $table->string('subject_name'); // বিষয়
            $table->decimal('written_marks', 5, 2)->default(0); // লিখিত নম্বর
            $table->decimal('oral_marks', 5, 2)->default(0); // মৌখিক নম্বর (হিফজ/কিতাব বিভাগের জন্য জরুরি)
            $table->decimal('total_marks', 5, 2); // মোট নম্বর
            $table->string('grade')->nullable(); // প্রাপ্ত গ্রেড (A+, A, B ইত্যাদি)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
