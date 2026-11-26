<?php

namespace App\Filament\Resources\TeacherPayrollResource\Pages;

use App\Filament\Resources\TeacherPayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherPayroll extends EditRecord
{
    protected static string $resource = TeacherPayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
