<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('madrasah_routines', function (Blueprint $table) {
            $table->time('start_time')->nullable(); // শুরুর সময়
            $table->time('end_time')->nullable();   // শেষের সময়
            $table->dropColumn('time_slot');        // পুরনো সময় স্লট কলামটি বাদ দেওয়া হলো
        });
    }

    public function down(): void
    {
        Schema::table('madrasah_routines', function (Blueprint $table) {
            $table->string('time_slot')->nullable();
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};