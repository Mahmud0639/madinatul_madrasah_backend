<?php

namespace App\Filament\Resources\TeacherLeaveResource\Pages;

use App\Filament\Resources\TeacherLeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherLeaves extends ListRecords
{
    protected static string $resource = TeacherLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
