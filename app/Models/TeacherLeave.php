<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'leave_type',
        'status',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function hasApprovedLeave(int $userId, string|Carbon $date): bool
    {
        $targetDate = Carbon::parse($date)->toDateString();

        return static::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->exists();
    }
}