<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    // নতুন ফি বা বেতন এন্ট্রি করার এপিআই (শুধুমাত্র অ্যাডমিন/অ্যাকাউন্ট্যান্ট করতে পারবে)
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_title' => 'required|string',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
        ]);

        $fee = Fee::create([
            'student_id' => $request->student_id,
            'fee_title' => $request->fee_title,
            'amount' => $request->amount,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => $request->due_date,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ছাত্রের ফি সফলভাবে নির্ধারণ করা হয়েছে!',
            'data' => $fee
        ], 201);
    }

    // নির্দিষ্ট ছাত্রের সমস্ত ফি ও বকেয়া দেখার এপিআই
    public function getStudentFees($student_id)
    {
        $fees = Fee::where('student_id', $student_id)->get();

        return response()->json([
            'success' => true,
            'data' => $fees
        ]);
    }
}
