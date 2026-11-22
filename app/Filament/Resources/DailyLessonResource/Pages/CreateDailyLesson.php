<?php

namespace App\Filament\Resources\DailyLessonResource\Pages;

use App\Filament\Resources\DailyLessonResource;
use App\Services\LessonAssignmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyLesson extends CreateRecord
{
    protected static string $resource = DailyLessonResource::class;

    protected function afterCreate(): void
    {
        $selectedIds = $this->data['target_student_ids'] ?? [];
        app(LessonAssignmentService::class)->syncStudentStatuses($this->record, $selectedIds);
    }
}