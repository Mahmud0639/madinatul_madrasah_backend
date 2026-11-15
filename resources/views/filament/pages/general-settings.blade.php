<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            সংরক্ষণ করুন (Save Changes)
        </x-filament::button>
    </form>
</x-filament-panels::page>