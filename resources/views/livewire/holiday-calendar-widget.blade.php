<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 space-y-6">
    {{-- হেডার ও মাস পরিবর্তনের বাটন --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-white/10 pb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                লাইভ ছুটি ক্যালেন্ডার — {{ $monthTitle }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                লাল গোল চিহ্নিত তারিখগুলো ছুটির দিন। মাস পরিবর্তন করলে নিচের ছুটির তালিকা স্বয়ংক্রিয়ভাবে আপডেট হবে।
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button
                type="button"
                wire:click="previousMonth"
                class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-white/15 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap"
            >
                &larr; আগের মাস
            </button>

            <button
                type="button"
                wire:click="goToCurrentMonth"
                class="px-3 py-1.5 text-sm font-medium rounded-lg border border-amber-500/40 bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition whitespace-nowrap"
            >
                চলতি মাস
            </button>

            <button
                type="button"
                wire:click="nextMonth"
                class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-white/15 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition whitespace-nowrap"
            >
                পরের মাস &rarr;
            </button>
        </div>
    </div>

    {{-- ক্যালেন্ডার গ্রিড ও সিলেক্টেড তারিখের তথ্য --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7">
            {{-- বারের নাম --}}
            <div class="grid grid-cols-7 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 pb-2">
                <div>শনি</div>
                <div>রবি</div>
                <div>সোম</div>
                <div>মঙ্গল</div>
                <div>বুধ</div>
                <div>বৃহঃ</div>
                <div style="color: #dc2626;">শুক্র</div>
            </div>

            {{-- তারিখসমূহ --}}
            <div class="grid grid-cols-7 gap-y-2 text-center">
                @foreach ($calendarDays as $cell)
                    @if (is_null($cell))
                        <div class="h-10"></div>
                    @else
                        @php
                            $isSelected = $selectedDate === $cell['date'];
                            $titlesTooltip = collect($cell['holidays'])->pluck('title')->implode(', ');

                            $inlineStyle = 'border-radius: 9999px;';
                            if ($cell['is_holiday']) {
                                $inlineStyle .= ' background-color: #dc2626; color: #ffffff; border: 2px solid #fca5a5;';
                            } elseif ($cell['is_today']) {
                                $inlineStyle .= ' border: 2px solid #f59e0b;';
                            }
                            if ($isSelected) {
                                $inlineStyle .= ' box-shadow: 0 0 0 2px #ef4444;';
                            }
                        @endphp
                        <div class="flex items-center justify-center h-10">
                            <button
                                type="button"
                                wire:click="selectDate('{{ $cell['date'] }}')"
                                title="{{ $titlesTooltip ?: $cell['date'] }}"
                                style="{{ $inlineStyle }}"
                                @class([
                                    'w-9 h-9 flex items-center justify-center text-sm font-semibold transition',
                                    'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800' => ! $cell['is_holiday'],
                                ])
                            >
                                {{ $cell['day'] }}
                            </button>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- লেজেন্ড --}}
            <div class="flex flex-wrap items-center gap-4 pt-4 mt-2 border-t border-gray-100 dark:border-white/5 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block w-3.5 h-3.5 rounded-full"
                        style="background-color: #dc2626;"
                    ></span>
                    <span>ছুটির দিন (লাল গোল)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span
                        class="inline-block w-3.5 h-3.5 rounded-full"
                        style="border: 2px solid #f59e0b;"
                    ></span>
                    <span>আজকের তারিখ</span>
                </div>
            </div>
        </div>

        {{-- নির্বাচিত মাসের ছুটির বিস্তারিত তালিকা --}}
        <div class="lg:col-span-5 border-t lg:border-t-0 lg:border-s border-gray-200 dark:border-white/10 pt-4 lg:pt-0 lg:ps-6 space-y-4">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $monthTitle }} মাসের নির্ধারিত ছুটিসমূহ
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    কোন ছুটি সরকারি এবং কোনটি মাদ্রাসা কর্তৃক তা নিচে দেওয়া হলো
                </p>
            </div>

            @if (count($monthHolidayList) > 0)
                <div class="space-y-2.5 max-h-72 overflow-y-auto pe-1">
                    @foreach ($monthHolidayList as $item)
                        @php
                            $startFormatted = \Carbon\Carbon::parse($item['start_date'])->format('d M, Y');
                            $endFormatted = \Carbon\Carbon::parse($item['end_date'])->format('d M, Y');
                            $dateLabel = $item['is_single_day'] ? $startFormatted : ($startFormatted . ' — ' . $endFormatted);

                            [$badgeLabel, $badgeStyle] = match ($item['type']) {
                                'public' => ['সরকারি ছুটি', 'background-color: #dbeafe; color: #1e40af;'],
                                'weekly' => ['সাপ্তাহিক ছুটি', 'background-color: #f3f4f6; color: #374151;'],
                                default => ['মাদ্রাসা কর্তৃক ছুটি', 'background-color: #d1fae5; color: #065f46;'],
                            };
                        @endphp
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-gray-800/50 flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold" style="color: #dc2626;">
                                    {{ $dateLabel }}
                                </div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $item['title'] }}
                                </div>
                                @if (! empty($item['note']))
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $item['note'] }}
                                    </div>
                                @endif
                            </div>

                            <span
                                class="shrink-0 px-2.5 py-1 text-[11px] font-semibold rounded-full"
                                style="{{ $badgeStyle }}"
                            >
                                {{ $badgeLabel }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 rounded-lg border border-dashed border-gray-300 dark:border-white/15 text-center text-sm text-gray-500 dark:text-gray-400">
                    এই মাসে বিশেষ বা সরকারি কোনো ছুটি সেট করা হয়নি (শুক্রবার ব্যতীত)।
                </div>
            @endif

            {{-- সিলেক্টেড তারিখের কুইক প্রিভিউ --}}
            @if ($selectedDate)
                <div class="pt-3 border-t border-gray-200 dark:border-white/10">
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                        নির্বাচিত তারিখ: {{ \Carbon\Carbon::parse($selectedDate)->format('d M, Y (l)') }}
                    </div>
                    @if (count($selectedDateHolidays) > 0)
                        <ul class="list-disc list-inside text-xs space-y-0.5" style="color: #dc2626;">
                            @foreach ($selectedDateHolidays as $sh)
                                <li>
                                    {{ $sh['title'] }}
                                    ({{ match($sh['type']) { 'public' => 'সরকারি ছুটি', 'weekly' => 'সাপ্তাহিক ছুটি', default => 'মাদ্রাসা কর্তৃক ছুটি' } }})
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            এই তারিখে কোনো ছুটি নেই (কর্মদিবস)।
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>