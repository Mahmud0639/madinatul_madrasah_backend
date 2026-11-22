<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // যেমন: নূরানী / নাজেরা সবক, হিফজ সবক, হোমওয়ার্ক
            $table->string('lesson_type')->default('sabak'); // sabak, homework, revision, sheet
            $table->text('body_template'); // আগে থেকে নির্ধারিত ফরম্যাট
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_templates');
    }
};