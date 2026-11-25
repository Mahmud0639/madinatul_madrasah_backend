<?php

namespace App\Filament\Resources\ExamResultResource\Pages;

use App\Filament\Resources\ExamResultResource;
use App\Models\ExamResult;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateExamResult extends CreateRecord
{
    protected static string $resource = ExamResultResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $rows = $data['subjects_entry'] ?? [];

        if (empty($rows) || ! is_array($rows)) {
            throw ValidationException::withMessages([
                'subjects_entry' => 'অন্তত একটি বিষয়ের ফলাফল যুক্ত করুন।',
            ]);
        }

        $firstRecord = null;

        foreach ($rows as $row) {
            $record = ExamResult::create([
                'student_id' => $data['student_id'],
                'exam_name' => $data['exam_name'],
                'subject_name' => $row['subject_name'],
                'written_marks' => $row['written_marks'] ?? 0,
                'oral_marks' => $row['oral_marks'] ?? 0,
                'full_marks' => $row['full_marks'] ?? 100,
                'pass_marks' => $row['pass_marks'] ?? 33,
                'result_status' => $row['result_status'] ?? 'passed',
                'remarks' => $row['remarks'] ?? null,
            ]);

            if (! $firstRecord) {
                $firstRecord = $record;
            }
        }

        return $firstRecord;
    }
}