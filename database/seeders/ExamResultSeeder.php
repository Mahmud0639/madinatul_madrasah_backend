<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExamResult;
use App\Models\Student;

class ExamResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ডাটাবেজ থেকে প্রথম ছাত্রটিকে খুঁজে নেওয়া (যার বিপরীতে ফলাফল সেভ হবে)
        $student = Student::first();

        if ($student) {
            // ১ম ডামি ফলাফল
            ExamResult::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'exam_name' => '১ম সাময়িক পরীক্ষা ২০২৬',
                    'subject_name' => 'আরবি সাহিত্য',
                ],
                [
                    'written_marks' => 75.00,
                    'oral_marks' => 20.00,
                    'total_marks' => 95.00,
                    'grade' => 'A+',
                ]
            );

            // ২য় ডামি ফলাফল
            ExamResult::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'exam_name' => '১ম সাময়িক পরীক্ষা ২০২৬',
                    'subject_name' => 'গণিত',
                ],
                [
                    'written_marks' => 65.00,
                    'oral_marks' => 15.00,
                    'total_marks' => 80.00,
                    'grade' => 'A+',
                ]
            );
        }
    }
}