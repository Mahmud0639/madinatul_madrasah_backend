<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    // নতুন ছাত্র ভর্তি বা রেজিস্ট্রেশন করার এপিআই
    public function store(Request $request)
    {
        // ডাটা ভ্যালিডেশন
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'roll_number' => 'required|string',
            'jamatu_class' => 'required|string',
            'rfid_card_number' => 'nullable|string|unique:students,rfid_card_number',
        ]);

        // ১. প্রথমে users টেবিলে কমন তথ্য সেভ করা
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        // ২. এরপর students টেবিলে মাদ্রাসার সুনির্দিষ্ট তথ্য সেভ করা
        $student = Student::create([
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'rfid_card_number' => $request->rfid_card_number,
            'roll_number' => $request->roll_number,
            'jamatu_class' => $request->jamatu_class,
            'section' => $request->section ?? 'non_residential',
            'date_of_birth' => $request->date_of_birth,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ছাত্র সফলভাবে রেজিস্টার করা হয়েছে!',
            'data' => [
                'user' => $user,
                'student' => $student
            ]
        ], 201);
    }
}
