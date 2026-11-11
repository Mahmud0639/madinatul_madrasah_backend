<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DiaryController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\ExamResultController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Http;


// ফোন নম্বর অথবা ইমেইল দিয়ে লগইন করার রুট
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/students/store', [StudentController::class, 'store']);
});

// RFID এটেনডেন্স পাঞ্চ রুট
Route::post('/attendance/punch', [AttendanceController::class, 'punchAttendance']);

// ডিজিটাল ডায়রি সংক্রান্ত রুটসমূহ
Route::middleware('auth:sanctum')->group(function () {
    // ডায়েরি এন্ট্রি করার রুট (এর জন্য 'create-diary' পারমিশন লাগবে)
    Route::post('/diary/store', [DiaryController::class, 'store'])->middleware('permission:create-diary');
    
    // নির্দিষ্ট ক্লাসের ডায়েরি দেখার রুট (এর জন্য 'view-diary' পারমিশন লাগবে)
    Route::get('/diary/{jamatu_class}', [DiaryController::class, 'getDiaryByClass'])->middleware('permission:view-diary');
});

Route::middleware('auth:sanctum')->group(function () {
    // নতুন ফি নির্ধারণ বা এন্ট্রি করার রুট ('create-fee' পারমিশন লাগবে)
    Route::post('/fee/store', [FeeController::class, 'store'])->middleware('permission:create-fee');
    
    // নির্দিষ্ট ছাত্রের ফি ও বকেয়া দেখার রুট ('view-fee' পারমিশন লাগবে)
    Route::get('/fee/student/{student_id}', [FeeController::class, 'getStudentFees'])->middleware('permission:view-fee');
});

Route::middleware('auth:sanctum')->group(function () {
    // পরীক্ষার ফলাফল বা নম্বর এন্ট্রি করার রুট ('create-result' পারমিশন লাগবে)
    Route::post('/exam-result/store', [ExamResultController::class, 'store'])->middleware('permission:create-result');
    
    // নির্দিষ্ট ছাত্রের পরীক্ষার ফলাফল দেখার রুট ('view-result' পারমিশন লাগবে)
    Route::get('/exam-result/student/{student_id}', [ExamResultController::class, 'getStudentResults'])->middleware('permission:view-result');
});

// এআই সংক্রান্ত রুটসমূহ
Route::middleware('auth:sanctum')->group(function () {
    // ১. অটোমেটিক নোটিশ জেনারেটর
    Route::post('/ai/generate-notice', [AIController::class, 'generateNotice']);

    // ২. রেজাল্ট অ্যানালাইসিস
    Route::post('/ai/analyze-result', [AIController::class, 'analyzeStudentResult']);

    // ৩. স্মার্ট চ্যাটবট
    Route::post('/ai/chatbot', [AIController::class, 'chatbot']);

    // ৪. ভয়েস-টু-টেক্সট (ডিজিটাল ডায়রির জন্য)
    Route::post('/ai/speech-to-text', [AIController::class, 'speechToText']);
    // ৫. এআই স্মার্ট ডেটা এন্ট্রি (নতুন যুক্ত হলো)
    Route::post('/ai/smart-data-entry', [AIController::class, 'smartDataEntry']);
    Route::post('/ai/smart-fee-entry', [AIController::class, 'smartFeeEntry']);
});

Route::get('/ai/test-models', function () {
    $apiKey = config('services.gemini.key');
    
    $response = Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

    return response()->json([
        'status' => $response->successful(),
        'status_code' => $response->status(),
        'response_data' => $response->json()
    ]);
});