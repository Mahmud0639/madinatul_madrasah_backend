<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diary;
use Illuminate\Http\Request;

class DiaryController extends Controller
{
    // শিক্ষক কর্তৃক ডিজিটাল ডায়রি বা হোমওয়ার্ক এন্ট্রি করার এপিআই
    public function store(Request $request)
    {
        $request->validate([
            'jamatu_class' => 'required|string',
            'subject_name' => 'required|string',
            'teacher_id' => 'required|exists:users,id',
            'homework_details' => 'required|string',
            'submission_date' => 'required|date',
        ]);

        $diary = Diary::create([
            'jamatu_class' => $request->jamatu_class,
            'subject_name' => $request->subject_name,
            'teacher_id' => $request->teacher_id,
            'homework_details' => $request->homework_details,
            'submission_date' => $request->submission_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ডিজিটাল ডায়রি বা হোমওয়ার্ক সফলভাবে সংরক্ষণ করা হয়েছে!',
            'data' => $diary
        ], 201);
    }

    // নির্দিষ্ট ক্লাসের বা জামাতের ডায়রি দেখার এপিআই
    public function getDiaryByClass($jamatu_class)
    {
        $diaries = Diary::where('jamatu_class', $jamatu_class)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $diaries
        ]);
    }
}
