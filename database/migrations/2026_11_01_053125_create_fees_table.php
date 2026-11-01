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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('fee_title'); // যেমন: জানুয়ারি মাসের বেতন বা ভর্তি ফি
            $table->decimal('amount', 10, 2); // মোট পরিমাণ
            $table->decimal('paid_amount', 10, 2)->default(0); // পরিশোধিত পরিমাণ
            $table->enum('status', ['paid', 'unpaid', 'partial'])->default('unpaid');
            $table->date('due_date'); // পরিশোধের শেষ তারিখ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
