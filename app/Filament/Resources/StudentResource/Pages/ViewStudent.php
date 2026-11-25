<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected static ?string $title = 'শিক্ষার্থীর পূর্ণাঙ্গ প্রোফাইল';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('তথ্য সম্পাদনা')
                ->color('warning'),
        ];
    }
}