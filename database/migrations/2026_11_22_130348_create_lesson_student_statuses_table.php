<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_student_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_lesson_id')
                ->constrained('daily_lessons')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->index();

            // নির্দিষ্ট ছাত্রকে আলাদা পড়া দিলে true, পুরো ক্লাসের কমন পড়া হলে false
            $table->boolean('is_targeted')->default(false);

            // গার্ডিয়ান ট্র্যাকিং
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->text('guardian_note')->nullable();

            // শিক্ষকের মূল্যায়ন: pending, completed_good, partial_revision, not_done
            $table->string('teacher_status')->default('pending')->index();
            $table->text('teacher_remark')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->unique(['daily_lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_student_statuses');
    }
};