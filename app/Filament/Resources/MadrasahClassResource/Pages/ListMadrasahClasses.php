<?php

namespace App\Filament\Resources\MadrasahClassResource\Pages;

use App\Filament\Resources\MadrasahClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMadrasahClasses extends ListRecords
{
    protected static string $resource = MadrasahClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
