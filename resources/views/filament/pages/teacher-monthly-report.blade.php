<x-filament-panels::page>
    <div class="space-y-6">
        <!-- ফিল্টার কার্ড -->
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>

        <!-- পাই চার্ট উইজেট (একবার এবং সঠিক মাপে দেখানোর জন্য) -->
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
            <div class="max-w-xl mx-auto">
                @livewire(\App\Filament\Widgets\TeacherAttendanceChart::class, [
                    'teacherId' => $this->data['teacher_id'] ?? null,
                    'month' => $this->data['month'] ?? now()->format('Y-m'),
                ])
            </div>
        </div>

        <!-- সারসংক্ষেপ এবং নির্দিষ্ট দিনের লেট হিস্ট্রি টেবিল -->
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                মাসিক উপস্থিতি ও বিলম্বের বিস্তারিত বিবরণী
            </h3>

            @if(!empty($summary))
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-sm text-gray-500">মোট উপস্থিত দিন</span>
                        <h4 class="text-xl font-bold text-green-600">{{ $summary['total_present'] }} দিন</h4>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-sm text-gray-500">মোট বিলম্ব (লেট)</span>
                        <h4 class="text-xl font-bold text-amber-600">{{ $summary['total_late'] }} দিন ({{ $summary['total_lateness_minutes'] }} মিনিট)</h4>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-sm text-gray-500">মোট অনুপস্থিত</span>
                        <h4 class="text-xl font-bold text-red-600">{{ $summary['total_absent'] }} দিন</h4>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-sm text-gray-500">মোট ডিউটি সময়</span>
                        <h4 class="text-xl font-bold text-blue-600">{{ $summary['total_hours'] }} ঘণ্টা</h4>
                    </div>
                </div>

                <!-- তারিখ ভিত্তিক ডিটেইলস টেবিল -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b dark:border-gray-800 text-gray-500 text-sm">
                                <th class="py-3 px-4">তারিখ</th>
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
                                    <td class="py-3 px-4">
                                        @if($record->status == 'present')
                                            <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Present</span>
                                        @elseif($record->status == 'late')
                                            <span class="px-2 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full">Late</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">Absent</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->in_time ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->out_time ?? '-' }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->lateness_minutes }} মিনিট</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $record->total_hours }} ঘণ্টা</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-500">এই মাসে কোনো হাজিরা রেকর্ড পাওয়া যায়নি।</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">দয়া করে একজন শিক্ষক সিলেক্ট করুন।</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>