<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('payroll_month', 7); // যেমন: 2026-11
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);

            // অনুপস্থিতি / বিলম্বের হিসাব ও হুজুরের ম্যানুয়াল নিয়ন্ত্রণ
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0);
            $table->decimal('calculated_deduction', 10, 2)->default(0); // সিস্টেমের হিসাবকৃত কর্তন
            $table->decimal('manual_deduction', 10, 2)->nullable(); // হুজুরের ওভাররাইড কর্তন (null হলে calculated_deduction ব্যবহৃত হবে)
            $table->decimal('final_deduction', 10, 2)->default(0);
            $table->text('deduction_note')->nullable(); // কর্তন বা ছাড়ের কারণ

            $table->decimal('net_salary', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partial, paid

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'payroll_month']);
            $table->index(['payroll_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_payrolls');
    }
};