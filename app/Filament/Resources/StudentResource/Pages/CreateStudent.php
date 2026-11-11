<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;
protected function getFormActions(): array
    {
        // পেজের নিচের ডিফল্ট গ্লোবাল ক্রিয়েট/ক্যান্সেল বাটনগুলো পুরোপুরি লুকিয়ে রাখা হলো
        return [];
    }
}
