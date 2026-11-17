<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); // এখানে $table-> যুক্ত করে দেওয়া হয়েছে
            $table->string('name')->unique(); // যেমন: হিফজ বিভাগ, নূরানি বিভাগ, কিতাব বিভাগ
            $table->string('code')->nullable(); // যেমন: HIFZ, NNR, KITAB
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};