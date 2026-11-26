<x-filament-panels::page>
    <div class="space-y-6">
        <!-- ফিল্টার কার্ড -->
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>

        <!-- পাই চার্ট ও বার চার্ট পাশাপাশি -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <div class="p-5 bg-white dark:bg-gray-900 shadow rounded-xl flex flex-col justify-between min-w-0">
                <div class="max-h-[320px] w-full flex items-center justify-center">
                    @livewire(\App\Filament\Widgets\TeacherAttendanceChart::class, [
                        'teacherId' => ($this->data['teacher_id'] ?? 'all') !== 'all' ? (int) $this->data['teacher_id'] : null,
                        'month' => $this->data['month'] ?? now()->format('Y-m'),
                    ], key('pie-' . ($this->data['teacher_id'] ?? 'all') . '-' . ($this->data['month'] ?? now()->format('Y-m'))))
                </div>
            </div>

            <div class="p-5 bg-white dark:bg-gray-900 shadow rounded-xl flex flex-col justify-between min-w-0">
                <div class="max-h-[320px] w-full flex items-center justify-center">
                    @livewire(\App\Filament\Widgets\TeacherPerformanceBarChart::class, [
                        'teacherId' => $this->data['teacher_id'] ?? 'all',
                        'month' => $this->data['month'] ?? now()->format('Y-m'),
                    ], key('bar-' . ($this->data['teacher_id'] ?? 'all') . '-' . ($this->data['month'] ?? now()->format('Y-m'))))
                </div>
            </div>
        </div>

        <!-- সকল শিক্ষকের মাসিক সামারি টেবিল -->
        @if(($this->data['teacher_id'] ?? 'all') === 'all')
            <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        সকল শিক্ষকের মাসিক উপস্থিতি, বিলম্ব ও পারফরম্যান্স রিপোর্ট
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b dark:border-gray-800 text-gray-500 text-sm">
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">শিক্ষকের নাম</th>
                                <th class="py-3 px-4">মোট উপস্থিতি</th>
                                <th class="py-3 px-4">মোট বিলম্ব (লেট)</th>
                                <th class="py-3 px-4">মোট অনুপস্থিতি</th>
                                <th class="py-3 px-4">মোট কর্মঘণ্টা</th>
                                <th class="py-3 px-4">মোট লেট (ঘণ্টায়)</th>
                                <th class="py-3 px-4">পারফরম্যান্স (১০০%)</th>
                                <th class="py-3 px-4">মন্তব্য</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-800 text-sm">
                            @forelse ($allTeachersReport as $index => $row)
                                @php
                                    $score = $row['performance_score'];
                                    $scoreClass = $score >= 90
                                        ? 'text-green-700 bg-green-100 dark:bg-green-900/40 dark:text-green-300'
                                        : ($score >= 75
                                            ? 'text-blue-700 bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300'
                                            : ($score >= 60
                                                ? 'text-amber-700 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-300'
                                                : 'text-red-700 bg-red-100 dark:bg-red-900/40 dark:text-red-300'));
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $index + 1 }}</td>
                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                    <td class="py-3 px-4 text-green-600 font-medium">{{ $row['total_present'] }} দিন</td>
                                    <td class="py-3 px-4 text-amber-600 font-medium">{{ $row['total_late'] }} দিন</td>
                                    <td class="py-3 px-4 text-red-600 font-medium">{{ $row['total_absent'] }} দিন</td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $row['total_hours'] }} ঘণ্টা</td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $row['late_in_hours_text'] }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $scoreClass }}">
                                            {{ $row['performance_score'] }}%
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $row['remark'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-6 text-center text-gray-500">
                                        এই মাসে শিক্ষকদের কোনো হাজিরা রেকর্ড পাওয়া যায়নি।
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- নির্দিষ্ট শিক্ষকের দিনভিত্তিক ও মাসিক সামারি -->
            <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    মাসিক উপস্থিতি, দিনভিত্তিক লগ ও পারফরম্যান্স বিবরণী
                </h3>

                @if(!empty($summary))
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <span class="text-sm text-gray-500">মোট উপস্থিত দিন</span>
                            <h4 class="text-xl font-bold text-green-600">{{ $summary['total_present'] }} দিন</h4>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <span class="text-sm text-gray-500">মোট বিলম্ব (লেট)</span>
                            <h4 class="text-xl font-bold text-amber-600">{{ $summary['total_late'] }} দিন ({{ $summary['late_in_hours_text'] }})</h4>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <span class="text-sm text-gray-500">মোট অনুপস্থিত</span>
                            <h4 class="text-xl font-bold text-red-600">{{ $summary['total_absent'] }} দিন</h4>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <span class="text-sm text-gray-500">মোট কর্মঘণ্টা ও পারফরম্যান্স</span>
                            <h4 class="text-xl font-bold text-blue-600">{{ $summary['total_hours'] }} ঘণ্টা ({{ $summary['performance_score'] }}%)</h4>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b dark:border-gray-800 text-gray-500 text-sm">
                                    <th class="py-3 px-4">তারিখ</th>
                                    <th class="py-3 px-4">বার</th>
                                    <th class="py-3 px-4">স্ট্যাটাস</th>
                                    <th class="py-3 px-4">ইন-টাইম</th>
                                    <th class="py-3 px-4">আউট-টাইম</th>
                                    <th class="py-3 px-4">লেট মিনিট</th>
                                    <th class="py-3 px-4">কর্মঘণ্টা</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-800 text-sm">
                                @forelse ($summary['records'] as $record)
                                    <tr>
                                        <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $record->date }}</td>
                                        <td class="py-3 px-4 text-gray-700 dark:text-gray-300">{{ $record->bangla_day }}</td>
                                        <td class="py-3 px-4">
                                            @if($record->status == 'present')
                                                <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">উপস্থিত</span>
                                            @elseif($record->status == 'late')
                                                <span class="px-2 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full">বিলম্ব</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">অনুপস্থিত</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->in_time ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->out_time ?? '-' }}</td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->lateness_minutes }} মিনিট</td>
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->total_hours }} ঘণ্টা</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-gray-500">এই মাসে কোনো হাজিরা রেকর্ড পাওয়া যায়নি।</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- নির্দিষ্ট শিক্ষকের সর্বশেষ সারসংক্ষেপ ও মন্তব্য -->
                    <div class="mt-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">মাসিক মূল্যায়ন ও মন্তব্য</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">
                                {{ $summary['remark'] }} — পারফরম্যান্স: {{ $summary['performance_score'] }}%
                            </p>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            মোট লেট: <strong>{{ $summary['late_in_hours_text'] }}</strong> | মোট কর্মঘণ্টা: <strong>{{ $summary['total_hours'] }} ঘণ্টা</strong>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500">দয়া করে একজন শিক্ষক সিলেক্ট করুন।</p>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
