<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('madrasah_routines', function (Blueprint $table) {
            $table->string('subject_name')->nullable()->change();
            $table->string('teacher_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('madrasah_routines', function (Blueprint $table) {
            $table->string('subject_name')->nullable(false)->change();
            $table->string('teacher_name')->nullable(false)->change();
        });
    }
};