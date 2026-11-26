<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'type',
        'is_active',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function isHolidayDate(string|Carbon $date): bool
    {
        $targetDate = Carbon::parse($date)->toDateString();

        if (Carbon::parse($targetDate)->isFriday()) {
            return true;
        }

        return static::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->exists();
    }
}