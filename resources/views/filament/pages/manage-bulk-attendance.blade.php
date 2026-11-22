<x-filament-panels::page>
    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        @if(!empty($this->data['department_id']) && !empty($this->data['jamatu_class']))
            @if(count($students) > 0)
                <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200">শিক্ষার্থীদের উপস্থিতি ও সময় তালিকা</h3>
                        
                        <span class="px-3 py-1 text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/35 dark:text-success-300 rounded-full">
                            ✓ এই তারিখের হাজিরা সংরক্ষিত রয়েছে (এডিট করা যাবে)
                        </span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b dark:border-gray-700 text-gray-600 dark:text-gray-400 text-sm">
                                    <th class="py-3 px-3">রোল নং</th>
                                    <th class="py-3 px-3">ছাত্র-ছাত্রীর বিবরণ (ছবি ও নাম)</th>
                                    <th class="py-3 px-3">উপস্থিতি স্ট্যাটাস</th>
                                    <th class="py-3 px-3">প্রবেশের সময় (In-Time)</th>
                                    <th class="py-3 px-3">প্রস্থানের সময় (Out-Time)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    <tr class="border-b dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-3 px-3 font-semibold text-gray-700 dark:text-gray-300">
                                            {{ $student->roll_number ?? 'N/A' }}
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="flex items-center gap-3">
                                                @if($student->photo)
                                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-700 shadow-sm">
                                                @else
                                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold text-sm">
                                                        {{ mb_substr($student->name, 0, 1) }}
                                                    </div>
                                                @endif
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $student->name }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="flex items-center gap-4">
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" wire:model="attendanceStatuses.{{ $student->id }}" value="present" class="text-success-600 focus:ring-success-500">
                                                    <span class="text-success-600 font-semibold text-sm">উপস্থিত</span>
                                                </label>
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" wire:model="attendanceStatuses.{{ $student->id }}" value="absent" class="text-danger-600 focus:ring-danger-500">
                                                    <span class="text-danger-600 font-semibold text-sm">অনুপস্থিত</span>
                                                </label>
                                                <label class="flex items-center gap-1 cursor-pointer">
                                                    <input type="radio" wire:model="attendanceStatuses.{{ $student->id }}" value="late" class="text-warning-600 focus:ring-warning-500">
                                                    <span class="text-warning-600 font-semibold text-sm">বিলম্ব</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <input type="time" wire:model="inTimes.{{ $student->id }}" class="border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-1.5 text-sm focus:ring-primary-500 focus:border-primary-500">
                                        </td>
                                        <td class="py-3 px-3">
                                            <input type="time" wire:model="outTimes.{{ $student->id }}" class="border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-1.5 text-sm focus:ring-primary-500 focus:border-primary-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit" size="lg" color="success">
                            হাজিরা আপডেট ও সংরক্ষণ করুন
                        </x-filament::button>
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-16 px-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 text-center space-y-4">
                    <div class="flex items-center justify-center w-14 h-14 bg-amber-50 dark:bg-amber-900/30 rounded-full text-amber-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:white">এই তারিখে কোনো হাজিরার তথ্য পাওয়া যায়নি</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">শিক্ষক মহোদয়, এই ক্লাসের এই তারিখের হাজিরা এখনো নেওয়া হয়নি। নতুন হাজিরা শুরু করতে নিচের বাটনে ক্লিক করুন।</p>
                    </div>
                    <x-filament::button wire:click="createNewAttendanceForDate" color="warning" size="md">
                        এই তারিখের হাজিরা শুরু করুন
                    </x-filament::button>
                </div>
            @endif
        @else
            <div class="flex flex-col items-center justify-center py-16 px-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 text-center space-y-3">
                <div class="flex items-center justify-center w-14 h-14 bg-gray-100 dark:bg-gray-800 rounded-full text-gray-400 dark:text-gray-500">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.82-2.715C16.418 16.723 16 17.33 16 18c0 .243.023.481.069.72M12 14.25a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 0c-3.791 0-7.14 1.875-9.13 4.795M19.5 10.5h.008v.008h-.008V10.5z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">বিভাগ ও জামাত/ক্লাস নির্বাচন করা হয়নি</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">দয়া করে ওপরের ড্রপডাউন থেকে বিভাগ এবং জামাত বা ক্লাস নির্বাচন করুন।</p>
            </div>
        @endif
    </form>
</x-filament-panels::page>