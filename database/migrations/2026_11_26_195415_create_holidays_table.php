<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // যেমন: ঈদুল ফিতর, শবে বরাত, বার্ষিক ছুটি, শুক্রবার
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type')->default('madrasah'); // government, madrasah, weekly_off
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};