<x-filament-panels::page>
    <div class="space-y-6">
        <!-- ডেট পিকার ফর্ম -->
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>

        <!-- দৈনিক হাজিরা টেবিল -->
        <div class="p-6 bg-white dark:bg-gray-900 shadow rounded-xl">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                নির্বাচিত তারিখের শিক্ষকদের হাজিরা বিবরণী
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b dark:border-gray-800 text-gray-500 text-sm">
                            <th class="py-3 px-4">শিক্ষকের নাম</th>
                            <th class="py-3 px-4">স্ট্যাটাস</th>
                            <th class="py-3 px-4">ইন-টাইম</th>
                            <th class="py-3 px-4">আউট-টাইম</th>
                            <th class="py-3 px-4">বিলম্ব (লেট)</th>
                            <th class="py-3 px-4">মোট কর্মঘণ্টা</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-800 text-sm">
                        @forelse ($reportData as $row)
                            <tr>
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                <td class="py-3 px-4">
                                    @if($row['status'] == 'present')
                                        <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">উপস্থিত (Present)</span>
                                    @elseif($row['status'] == 'late')
                                        <span class="px-2 py-1 text-xs font-semibold text-amber-700 bg-amber-100 rounded-full">বিলম্বে (Late)</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">অনুপস্থিত (Absent)</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $row['in_time'] }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $row['out_time'] }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $row['lateness'] }}</td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">{{ $row['hours'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-500">কোনো তথ্য পাওয়া যায়নি।</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>