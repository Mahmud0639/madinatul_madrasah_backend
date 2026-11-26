<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherPayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_no',
        'user_id',
        'payroll_month',
        'basic_salary',
        'allowances',
        'bonus',
        'absent_days',
        'late_days',
        'calculated_deduction',
        'manual_deduction',
        'final_deduction',
        'deduction_note',
        'net_salary',
        'paid_amount',
        'due_amount',
        'status',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'bonus' => 'decimal:2',
            'calculated_deduction' => 'decimal:2',
            'manual_deduction' => 'decimal:2',
            'final_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TeacherPayroll $payroll) {
            if (empty($payroll->voucher_no)) {
                $payroll->voucher_no = 'SAL-' . str_replace('-', '', $payroll->payroll_month) . '-' . strtoupper(uniqid());
            }

            // হুজুর ম্যানুয়াল কর্তন দিলে সেটিই চূড়ান্ত হবে, নাহলে হিসাবকৃত কর্তন বসবে
            $finalDeduction = $payroll->manual_deduction !== null
                ? (float) $payroll->manual_deduction
                : (float) $payroll->calculated_deduction;

            $finalDeduction = max(0, $finalDeduction);
            $payroll->final_deduction = $finalDeduction;

            $gross = (float) $payroll->basic_salary
                + (float) $payroll->allowances
                + (float) $payroll->bonus;

            $netSalary = max(0, $gross - $finalDeduction);
            $payroll->net_salary = $netSalary;

            $paid = max(0, (float) $payroll->paid_amount);
            $due = max(0, $netSalary - $paid);
            $payroll->due_amount = $due;

            if ($paid <= 0) {
                $payroll->status = 'unpaid';
            } elseif ($due <= 0) {
                $payroll->status = 'paid';
            } else {
                $payroll->status = 'partial';
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TeacherSalaryPayment::class);
    }

    public function recalculateFromPayments(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;
        $this->save();
    }
}