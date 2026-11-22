<?php

namespace App\Filament\Resources\DailyLessonResource\Pages;

use App\Filament\Resources\DailyLessonResource;
use App\Services\LessonAssignmentService;
use Filament\Resources\Pages\EditRecord;

class EditDailyLesson extends EditRecord
{
    protected static string $resource = DailyLessonResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['target_type'] ?? 'all_class') === 'specific_students') {
            $data['target_student_ids'] = $this->record->studentStatuses()
                ->where('is_targeted', true)
                ->pluck('student_id')
                ->toArray();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $selectedIds = $this->data['target_student_ids'] ?? [];
        app(LessonAssignmentService::class)->syncStudentStatuses($this->record, $selectedIds);
    }
}