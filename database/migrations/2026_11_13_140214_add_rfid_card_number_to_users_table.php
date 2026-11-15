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
        Schema::table('users', function (Blueprint $table) {
            // এইখানে কোডটি দিতে হবে
            $table->string('rfid_card_number')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::table('users', function (Blueprint $table) {
            // মাইগ্রেশন রোলব্যাক করলে এই কলামটি ডিলিট হয়ে যাবে
            $table->dropColumn('rfid_card_number');
        });
    }
};