<?php

namespace App\Filament\Resources\MadrasahClassResource\Pages;

use App\Filament\Resources\MadrasahClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMadrasahClass extends EditRecord
{
    protected static string $resource = MadrasahClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
