<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('rfid_card_number')->unique()->nullable(); // RFID বা স্মার্ট কার্ড আইডি
            $table->string('roll_number'); // রোল নম্বর
            $table->string('jamatu_class'); // জামাত বা ক্লাস (যেমন: নূরানি, হিফজ, কিতাব বিভাগ)
            $table->enum('section', ['residential', 'non_residential'])->default('non_residential'); // আবাসিক বা অনাবাসিক
            $table->date('date_of_birth')->nullable(); // জন্ম তারিখ
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};