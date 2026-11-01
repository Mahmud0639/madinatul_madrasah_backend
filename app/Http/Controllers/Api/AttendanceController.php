<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // RFID কার্ড পাঞ্চ করার সাথে সাথে হাজিরা রেকর্ড করার এপিআই
    public function punchAttendance(Request $request)
    {
        $request->validate([
            'rfid_card_number' => 'required|string',
        ]);

        // ১. কার্ড নম্বর দিয়ে ডাটাবেস থেকে ছাত্রকে খোঁজা
        $student = Student::where('rfid_card_number', $request->rfid_card_number)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'ভুল বা নিবন্ধনহীন RFID কার্ড! ছাত্র পাওয়া যায়নি।'
            ], 404);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->toTimeString();

        // ২. আজকের দিনে এই ছাত্রের উপস্থিতি আগেই রেকর্ড হয়েছে কি না চেক করা
        $attendance = Attendance::where('student_id', $student->id)
                                ->where('date', $today)
                                ->first();

        if ($attendance) {
            // যদি ইন-টাইম থাকে কিন্তু আউট-টাইম না থাকে, তবে এটি ছুটির সময় (Out Time) হিসেবে আপডেট হবে
            if (!$attendance->out_time) {
                $attendance->update([
                    'out_time' => $currentTime
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'মাদ্রাসা ত্যাগের (Out Time) সময় সফলভাবে রেকর্ড করা হয়েছে!',
                    'student_name' => $student->user->name ?? '',
                    'data' => $attendance
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'আজকের জন্য এই ছাত্রের উপস্থিতি এবং প্রস্থান উভয়ই ইতিমধ্যে সম্পন্ন হয়েছে!'
                ], 400);
            }
        } else {
            // ৩. নতুন উপস্থিতি (In Time / প্রবেশের সময়) রেকর্ড করা
            $attendance = Attendance::create([
                'student_id' => $student->id,
                'date' => $today,
                'in_time' => $currentTime,
                'status' => 'present'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'মাদ্রাসায় সফলভাবে প্রবেশ করেছে (In Time Recorded)!',
                'student_name' => $student->user->name ?? '',
                'data' => $attendance
            ]);
        }
    }
}
