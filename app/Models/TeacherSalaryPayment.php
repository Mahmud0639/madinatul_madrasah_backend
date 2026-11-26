<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_payroll_id',
        'receipt_no',
        'amount',
        'payment_date',
        'payment_method',
        'reference_no',
        'note',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TeacherSalaryPayment $payment) {
            if (empty($payment->receipt_no)) {
                $payment->receipt_no = 'RCPT-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });

        static::saved(function (TeacherSalaryPayment $payment) {
            $payment->payroll?->recalculateFromPayments();
        });

        static::deleted(function (TeacherSalaryPayment $payment) {
            $payment->payroll?->recalculateFromPayments();
        });
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(TeacherPayroll::class, 'teacher_payroll_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}