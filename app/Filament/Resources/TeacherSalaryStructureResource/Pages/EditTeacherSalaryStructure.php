<?php

namespace App\Filament\Resources\TeacherSalaryStructureResource\Pages;

use App\Filament\Resources\TeacherSalaryStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherSalaryStructure extends EditRecord
{
    protected static string $resource = TeacherSalaryStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
