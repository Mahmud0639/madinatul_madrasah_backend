<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TeacherAttendanceController extends Controller
{
    public function punchAttendance(Request $request)
    {
        $request->validate([
            'rfid_card_number' => 'required|string',
        ]);

        // users টেবিল থেকে rfid_card_number এবং role 'teacher' চেক করে শিক্ষক খোঁজা
        $teacher = User::where('rfid_card_number', $request->rfid_card_number)
                       ->where('role', 'teacher')
                       ->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'শিক্ষক পাওয়া যায়নি বা ভুল RFID কার্ড!'
            ], 404);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();
        $currentTimeString = $currentTime->toTimeString();

        // আজকের দিনের হাজিরা রেকর্ড চেক করা
        $attendance = TeacherAttendance::where('user_id', $teacher->id)
            ->where('date', $today)
            ->first();

        if ($attendance) {
            // যদি ইন-টাইম থাকে কিন্তু আউট-টাইম না থাকে (অথবা ডিফল্ট সময় থাকে), তবে এটি হবে দ্বিতীয় পাঞ্চ (আউট-টাইম)
            if (!$attendance->out_time) {
                $inTime = Carbon::parse($attendance->in_time);
                $totalHours = round($inTime->diffInMinutes($currentTime) / 60, 2);

                $attendance->update([
                    'out_time' => $currentTimeString,
                    'total_hours' => $totalHours > 0 ? $totalHours : 0,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'শিক্ষকের প্রস্থানের (Out-Time) সময় সফলভাবে রেকর্ড হয়েছে!',
                    'data' => $attendance
                ]);
            } else {
                // যদি ইন এবং আউট দুটোই আগে থেকেই রেকর্ড হয়ে থাকে
                return response()->json([
                    'success' => false,
                    'message' => 'আজকের জন্য এই শিক্ষকের উপস্থিতি এবং প্রস্থান সম্পন্ন হয়েছে!'
                ], 400);
            }
        } else {
            // এটি হবে প্রথম পাঞ্চ (ইন-টাইম) - যা পাইচার্ট ও উপস্থিতি স্ট্যাটাস আপডেট করবে
            $officeStartTimeString = Cache::get('office_start_time', '08:00:00');
            $graceMinutes = (int) Cache::get('office_grace_minutes', 15);

            $status = 'present';
            $officialStartTime = Carbon::parse($officeStartTimeString);
            
            if ($currentTime->greaterThan($officialStartTime->addMinutes($graceMinutes))) {
                $status = 'late';
            }

            $attendance = TeacherAttendance::create([
                'user_id' => $teacher->id,
                'date' => $today,
                'in_time' => $currentTimeString,
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'শিক্ষক সফলভাবে প্রবেশ করেছেন (In-Time Recorded)!',
                'teacher_name' => $teacher->name,
                'data' => $attendance
            ]);
        }
    }
}