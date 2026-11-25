<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamGradingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grading_mode',
        'include_oral',
        'fail_on_any_subject',
        'grade_ranges',
        'is_active',
    ];

    protected $casts = [
        'include_oral' => 'boolean',
        'fail_on_any_subject' => 'boolean',
        'grade_ranges' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * মাদ্রাসা তাকদির/গ্রেড প্রিসেট
     *
     * @return array<int, array<string, mixed>>
     */
    public static function madrasahPreset(): array
    {
        return [
            ['label' => 'মুমতাজ', 'min_percentage' => 80, 'max_percentage' => 100, 'gpa' => 5.00, 'is_pass' => true, 'active' => true],
            ['label' => 'জায়্যিদ জিদ্দান', 'min_percentage' => 65, 'max_percentage' => 79.99, 'gpa' => 4.00, 'is_pass' => true, 'active' => true],
            ['label' => 'জায়্যিদ', 'min_percentage' => 50, 'max_percentage' => 64.99, 'gpa' => 3.50, 'is_pass' => true, 'active' => true],
            ['label' => 'মাকবুল', 'min_percentage' => 35, 'max_percentage' => 49.99, 'gpa' => 2.50, 'is_pass' => true, 'active' => true],
            ['label' => 'রাসিব', 'min_percentage' => 0, 'max_percentage' => 34.99, 'gpa' => 0.00, 'is_pass' => false, 'active' => true],
        ];
    }

    /**
     * স্কুল / বোর্ড গ্রেড প্রিসেট
     *
     * @return array<int, array<string, mixed>>
     */
    public static function schoolPreset(): array
    {
        return [
            ['label' => 'A+', 'min_percentage' => 80, 'max_percentage' => 100, 'gpa' => 5.00, 'is_pass' => true, 'active' => true],
            ['label' => 'A', 'min_percentage' => 70, 'max_percentage' => 79.99, 'gpa' => 4.00, 'is_pass' => true, 'active' => true],
            ['label' => 'A-', 'min_percentage' => 60, 'max_percentage' => 69.99, 'gpa' => 3.50, 'is_pass' => true, 'active' => true],
            ['label' => 'B', 'min_percentage' => 50, 'max_percentage' => 59.99, 'gpa' => 3.00, 'is_pass' => true, 'active' => true],
            ['label' => 'C', 'min_percentage' => 40, 'max_percentage' => 49.99, 'gpa' => 2.00, 'is_pass' => true, 'active' => true],
            ['label' => 'D', 'min_percentage' => 33, 'max_percentage' => 39.99, 'gpa' => 1.00, 'is_pass' => true, 'active' => true],
            ['label' => 'F', 'min_percentage' => 0, 'max_percentage' => 32.99, 'gpa' => 0.00, 'is_pass' => false, 'active' => true],
        ];
    }

    /**
     * সক্রিয় সেটিংস রেকর্ডটি নিয়ে আসা (না থাকলে ডিফল্ট তৈরি করা)
     */
    public static function activeSetting(): self
    {
        $setting = static::query()->where('is_active', true)->latest('id')->first();

        if ($setting) {
            return $setting;
        }

        return static::query()->create([
            'name' => 'মাদ্রাসা তাকদির সিস্টেম',
            'grading_mode' => 'madrasah',
            'include_oral' => true,
            'fail_on_any_subject' => true,
            'grade_ranges' => self::madrasahPreset(),
            'is_active' => true,
        ]);
    }

    /**
     * শতকরা ও পাস নম্বর অনুযায়ী গ্রেড, GPA এবং স্ট্যাটাস বের করা
     *
     * @return array{0: string, 1: float, 2: string}
     */
    public function evaluateByPercentage(float $total, float $percentage, float $passMarks): array
    {
        if ($total < $passMarks) {
            $failLabel = $this->grading_mode === 'school' ? 'F' : 'রাসিব';
            return [$failLabel, 0.00, 'failed'];
        }

        $ranges = collect($this->grade_ranges ?? [])
            ->filter(fn ($row) => (bool) ($row['active'] ?? true))
            ->sortByDesc(fn ($row) => (float) ($row['min_percentage'] ?? 0));

        foreach ($ranges as $row) {
            $min = (float) ($row['min_percentage'] ?? 0);
            $max = (float) ($row['max_percentage'] ?? 100);
            $label = trim((string) ($row['label'] ?? ''));
            $gpa = (float) ($row['gpa'] ?? 0);
            $isPass = (bool) ($row['is_pass'] ?? true);

            if ($label !== '' && $percentage >= $min && $percentage <= $max) {
                $status = $isPass ? 'passed' : 'failed';
                return [$label, $gpa, $status];
            }
        }

        $fallbackFail = $this->grading_mode === 'school' ? 'F' : 'রাসিব';
        return [$fallbackFail, 0.00, 'failed'];
    }
}