<?php

namespace App\Filament\Resources\MadrasahRoutineResource\Pages;

use App\Filament\Resources\MadrasahRoutineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMadrasahRoutine extends EditRecord
{
    protected static string $resource = MadrasahRoutineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
