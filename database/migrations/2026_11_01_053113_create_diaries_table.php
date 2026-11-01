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
        Schema::create('diaries', function (Blueprint $table) {
            $table->id();
            $table->string('jamatu_class'); // কোন জামাত বা ক্লাসের জন্য
            $table->string('subject_name'); // বিষয়ের নাম (যেমন: আরবি, গণিত)
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // কোন ওস্তাদ/শিক্ষক দিয়েছেন
            $table->text('homework_details'); // পড়ার বিবরণ বা হোমওয়ার্ক
            $table->date('submission_date'); // জমা দেওয়ার শেষ তারিখ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diaries');
    }
};
