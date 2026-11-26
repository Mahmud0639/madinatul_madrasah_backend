<?php

namespace App\Livewire;

use App\Models\Holiday;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class HolidayCalendarWidget extends Widget
{
    protected static string $view = 'livewire.holiday-calendar-widget';

    protected int | string | array $columnSpan = 'full';

    public int $year;

    public int $month;

    public ?string $selectedDate = null;

    public function mount(): void
    {
        $now = now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
        $this->selectedDate = $now->toDateString();
    }

    public function previousMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
        $this->selectedDate = $date->copy()->startOfMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = (int) $date->year;
        $this->month = (int) $date->month;
        $this->selectedDate = $date->copy()->startOfMonth()->toDateString();
    }

    public function goToCurrentMonth(): void
    {
        $now = now();
        $this->year = (int) $now->year;
        $this->month = (int) $now->month;
        $this->selectedDate = $now->toDateString();
    }

    public function selectDate(string $dateString): void
    {
        $this->selectedDate = $dateString;
    }

    protected function getViewData(): array
    {
        $startOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $holidays = Holiday::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $endOfMonth->toDateString())
            ->whereDate('end_date', '>=', $startOfMonth->toDateString())
            ->orderBy('start_date')
            ->get();

        $holidayMap = [];
        $monthHolidayList = [];

        foreach ($holidays as $holiday) {
            $rangeStart = Carbon::parse($holiday->start_date)->startOfDay();
            $rangeEnd = Carbon::parse($holiday->end_date)->startOfDay();

            $effectiveStart = $rangeStart->lt($startOfMonth) ? $startOfMonth->copy() : $rangeStart->copy();
            $effectiveEnd = $rangeEnd->gt($endOfMonth) ? $endOfMonth->copy() : $rangeEnd->copy();

            $cursor = $effectiveStart->copy();
            while ($cursor->lte($effectiveEnd)) {
                $dateKey = $cursor->toDateString();
                $holidayMap[$dateKey][] = [
                    'id' => $holiday->id,
                    'title' => $holiday->title,
                    'type' => $holiday->type,
                    'note' => $holiday->note,
                ];
                $cursor->addDay();
            }

            $monthHolidayList[] = [
                'id' => $holiday->id,
                'title' => $holiday->title,
                'type' => $holiday->type,
                'start_date' => $holiday->start_date,
                'end_date' => $holiday->end_date,
                'note' => $holiday->note,
                'is_single_day' => $rangeStart->isSameDay($rangeEnd),
            ];
        }

        $leadingBlanks = ($startOfMonth->dayOfWeek - Carbon::SATURDAY + 7) % 7;
        $daysInMonth = $startOfMonth->daysInMonth;

        $calendarDays = [];

        for ($i = 0; $i < $leadingBlanks; $i++) {
            $calendarDays[] = null;
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $current = Carbon::createFromDate($this->year, $this->month, $day);
            $dateStr = $current->toDateString();
            $isFriday = $current->isFriday();
            $dayHolidays = $holidayMap[$dateStr] ?? [];

            if ($isFriday && empty($dayHolidays)) {
                $dayHolidays[] = [
                    'id' => null,
                    'title' => 'সাপ্তাহিক ছুটি (শুক্রবার)',
                    'type' => 'weekly',
                    'note' => null,
                ];
            } elseif ($isFriday && ! empty($dayHolidays)) {
                array_unshift($dayHolidays, [
                    'id' => null,
                    'title' => 'সাপ্তাহিক ছুটি (শুক্রবার)',
                    'type' => 'weekly',
                    'note' => null,
                ]);
            }

            $calendarDays[] = [
                'day' => $day,
                'date' => $dateStr,
                'is_today' => $current->isToday(),
                'is_holiday' => ! empty($dayHolidays),
                'holidays' => $dayHolidays,
            ];
        }

        $banglaMonths = [
            1 => 'জানুয়ারি',
            2 => 'ফেব্রুয়ারি',
            3 => 'মার্চ',
            4 => 'এপ্রিল',
            5 => 'মে',
            6 => 'জুন',
            7 => 'জুলাই',
            8 => 'আগস্ট',
            9 => 'সেপ্টেম্বর',
            10 => 'অক্টোবর',
            11 => 'নভেম্বর',
            12 => 'ডিসেম্বর',
        ];

        $monthTitle = ($banglaMonths[$this->month] ?? $startOfMonth->format('F')) . ' ' . $this->year;

        return [
            'monthTitle' => $monthTitle,
            'calendarDays' => $calendarDays,
            'monthHolidayList' => $monthHolidayList,
            'selectedDateHolidays' => $this->selectedDate ? ($holidayMap[$this->selectedDate] ?? (
                Carbon::parse($this->selectedDate)->isFriday()
                    ? [[
                        'id' => null,
                        'title' => 'সাপ্তাহিক ছুটি (শুক্রবার)',
                        'type' => 'weekly',
                        'note' => null,
                    ]]
                    : []
            )) : [],
        ];
    }
}