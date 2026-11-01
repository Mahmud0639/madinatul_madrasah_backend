<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class ExamResultController extends Controller
{
    // পরীক্ষার ফলাফল বা নম্বর এন্ট্রি করার এপিআই
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'exam_name' => 'required|string',
            'subject_name' => 'required|string',
            'written_marks' => 'required|numeric',
            'oral_marks' => 'nullable|numeric',
            'total_marks' => 'required|numeric',
            'grade' => 'nullable|string',
        ]);

        $result = ExamResult::create([
            'student_id' => $request->student_id,
            'exam_name' => $request->exam_name,
            'subject_name' => $request->subject_name,
            'written_marks' => $request->written_marks,
            'oral_marks' => $request->oral_marks ?? 0,
            'total_marks' => $request->total_marks,
            'grade' => $request->grade,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'পরীক্ষার ফলাফল সফলভাবে সংরক্ষণ করা হয়েছে!',
            'data' => $result
        ], 201);
    }

    // নির্দিষ্ট ছাত্রের সকল পরীক্ষার ফলাফল দেখার এপিআই
    public function getStudentResults($student_id)
    {
        $results = ExamResult::where('student_id', $student_id)->get();

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}
