<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_payroll_id')->constrained('teacher_payrolls')->cascadeOnDelete();
            $table->string('receipt_no')->unique();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('cash'); // cash, bkash, nagad, bank
            $table->string('reference_no')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['teacher_payroll_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_salary_payments');
    }
};