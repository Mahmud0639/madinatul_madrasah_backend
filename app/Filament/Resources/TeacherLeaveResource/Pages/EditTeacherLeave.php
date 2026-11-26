<?php

namespace App\Filament\Resources\TeacherLeaveResource\Pages;

use App\Filament\Resources\TeacherLeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherLeave extends EditRecord
{
    protected static string $resource = TeacherLeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
