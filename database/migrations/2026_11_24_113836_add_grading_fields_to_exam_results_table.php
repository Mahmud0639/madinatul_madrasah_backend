<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->decimal('full_marks', 6, 2)->default(100)->after('oral_marks');
            $table->decimal('pass_marks', 6, 2)->default(33)->after('full_marks');
            $table->decimal('percentage', 5, 2)->nullable()->after('total_marks');
            $table->decimal('gpa', 3, 2)->nullable()->after('grade');
            $table->string('result_status', 20)->default('passed')->after('gpa'); // passed, failed, absent
            $table->text('remarks')->nullable()->after('result_status');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn([
                'full_marks',
                'pass_marks',
                'percentage',
                'gpa',
                'result_status',
                'remarks',
            ]);
        });
    }
};