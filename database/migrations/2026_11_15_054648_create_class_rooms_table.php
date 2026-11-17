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
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ক্লাসের নাম (যেমন: হিফজ বিভাগ, ক্লাস ওয়ান)
            $table->text('description')->nullable(); // ক্লাসের বিবরণ (ঐচ্ছিক)
            $table->integer('sort_order')->default(0); // সিরিয়াল সাজানোর জন্য
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};