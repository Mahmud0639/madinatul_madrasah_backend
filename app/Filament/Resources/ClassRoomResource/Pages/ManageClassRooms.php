<?php

namespace App\Filament\Resources\ClassRoomResource\Pages;

use App\Filament\Resources\ClassRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageClassRooms extends ManageRecords
{
    protected static string $resource = ClassRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}