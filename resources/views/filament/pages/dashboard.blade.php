<x-filament-panels::page>
    <div class="space-y-6">
        <!-- ওপরে সবুজ ব্যানার ও স্ট্যাটাস কার্ড -->
        <x-filament-widgets::widgets
            :widgets="$this->getTopWidgets()"
            :columns="2"
        />

        <!-- ট্যাব নেভিগেশন -->
        <div class="flex flex-wrap gap-2 p-2 bg-white dark:bg-gray-900 rounded-xl shadow border dark:border-gray-800">
            <button
                type="button"
                wire:click="setTab('all')"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'all' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                সকল চার্ট ও পরিসংখ্যান
            </button>

            <button
                type="button"
                wire:click="setTab('attendance')"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'attendance' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                শিক্ষক হাজিরা (পাই ও বার চার্ট)
            </button>

            <button
                type="button"
                wire:click="setTab('students')"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'students' ? 'bg-amber-500 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
            >
                বিভাগভিত্তিক শিক্ষার্থী
            </button>
        </div>

        <!-- ট্যাবের নিচে পাই চার্ট, বার চার্ট ও অন্যান্য চার্ট -->
        <x-filament-widgets::widgets
            :widgets="$this->getTabWidgets()"
            :columns="2"
        />
    </div>
</x-filament-panels::page>