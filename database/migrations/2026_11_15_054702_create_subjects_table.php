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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            // class_room_id এর পরিবর্তে madrasah_class_id করা হলো এবং সঠিক টেবিল রেফারেন্স দেওয়া হলো
            $table->foreignId('madrasah_class_id')->constrained('madrasah_classes')->onDelete('cascade'); 
            $table->string('subject_name'); // বিষয়ের নাম (যেমন: সবক, কায়দা, গণিত)
            $table->string('code')->nullable(); // সাবজেক্ট কোড (ঐচ্ছিক)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};