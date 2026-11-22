<?php

namespace App\Filament\Resources\DailyLessonResource\Pages;

use App\Filament\Resources\DailyLessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyLessons extends ListRecords
{
    protected static string $resource = DailyLessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
