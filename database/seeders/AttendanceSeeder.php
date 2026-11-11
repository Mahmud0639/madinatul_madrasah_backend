<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ডেটাবেজ থেকে প্রথম ছাত্রটিকে খুঁজে নেওয়া
        $student = Student::first();

        if ($student) {
            // ১. আজকের হাজিরা (উপস্থিত)
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => Carbon::today()->toDateString(),
                ],
                [
                    'in_time' => '08:00:00', // মাদ্রাসায় প্রবেশের সময়
                    'out_time' => '16:00:00', // মাদ্রাসা ত্যাগের সময়[cite: 23]
                    'status' => 'present', // স্ট্যাটাস[cite: 23]
                ]
            );

            // ২. গতকালের হাজিরা (বিলম্ব / Late)
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => Carbon::yesterday()->toDateString(),
                ],
                [
                    'in_time' => '08:20:00',
                    'out_time' => '16:00:00',
                    'status' => 'late',
                ]
            );
        }
    }
}