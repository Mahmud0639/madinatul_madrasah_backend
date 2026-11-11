<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use App\Models\User;
use App\Models\ExamResult;
use App\Models\Fee;
use Illuminate\Http\Request;

class AIController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * ১. অটোমেটিক নোটিশ জেনারেটর
     */
    public function generateNotice(Request $request)
    {
        $request->validate([
            'topic' => 'required|string',
        ]);

        $prompt = "তুমি একটি মাদ্রাসার প্রাতিষ্ঠানিক এআই অ্যাসিস্ট্যান্ট। নিচের বিষয়টি নিয়ে একটি অত্যন্ত সুন্দর, শালীন ও প্রাতিষ্ঠানিক বাংলা নোটিশ বা বিজ্ঞপ্তি তৈরি করো:\n\nবিষয়: {$request->topic}\n\nনোটিশের ভাষা প্রাতিষ্ঠানিক ও পরিপাটি হতে হবে।";

        $result = $this->aiService->generateText($prompt);

        return response()->json([
            'status' => true,
            'notice' => $result
        ]);
    }

    /**
     * ২. রেজাল্ট ও পারফরম্যান্স অ্যানালাইসিস
     */
    public function analyzeStudentResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $student = User::find($request->student_id);
        $results = ExamResult::where('student_id', $request->student_id)->get();

        if ($results->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'এই ছাত্রের কোনো পরীক্ষার নম্বর পাওয়া যায়নি।'
            ], 404);
        }

        $resultData = $results->map(function ($r) {
            return "পরীক্ষা: {$r->exam_name}, বিষয়: {$r->subject_name}, লিখিত: {$r->written_marks}, মৌখিক: {$r->oral_marks}, মোট নম্বর: {$r->total_marks}, গ্রেড: {$r->grade}";
        })->implode(", ");

        $prompt = "ছাত্রের নাম: {$student->name}\nপ্রাপ্ত নম্বরসমূহ: {$resultData}\n\nউপরের নম্বর বিশ্লেষণ করে ছাত্রের শক্তি ও দুর্বলতার জায়গা সংক্ষেপে চিহ্নিত করো এবং তার উন্নতির জন্য শিক্ষণীয় পরামর্শ বাংলা ভাষায় ৩-৪ লাইনে লেখো।";

        $analysis = $this->aiService->generateText($prompt);

        return response()->json([
            'status' => true,
            'student_name' => $student->name,
            'analysis' => $analysis
        ]);
    }

    /**
     * ৩. স্মার্ট চ্যাটবট বা হেল্পডেস্ক
     */
    public function chatbot(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
        ]);

        $user = $request->user();
        $context = "ইউজারের নাম: {$user->name}, রোল: {$user->role}.\n";

        // ছাত্র বা অভিভাবক হলে বকেয়া বেতনের তথ্য কনটেক্সটে যুক্ত করা
        if (in_array($user->role, ['student', 'parent'])) {
            $studentId = $user->role === 'student' ? $user->id : $request->student_id ?? $user->id;
            $dueFee = Fee::where('student_id', $studentId)->where('status', 'unpaid')->sum('amount');
            $context .= "ছাত্রের বর্তমান বকেয়া বেতন: {$dueFee} টাকা।\n";
        }

        $prompt = "তুমি একটি মাদ্রাসা ম্যানেজমেন্ট সিস্টেমের হেল্পডেস্ক এআই। নিচের তথ্য ও ইউজারের প্রশ্নের উত্তর দাও।\n\nতথ্য:\n{$context}\nপ্রশ্ন: {$request->query}\n\nখুবই সংক্ষিপ্ত, স্পষ্ট ও বন্ধুত্বপূর্ণ বাংলা ভাষায় উত্তর দাও।";

        $answer = $this->aiService->generateText($prompt);

        return response()->json([
            'status' => true,
            'response' => $answer
        ]);
    }

    /**
     * ৪. ভয়েস-টু-টেক্সট (ডিজিটাল ডায়রি/হোমওয়ার্ক)
     */
    public function speechToText(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg,webm|max:10240', // সর্বোচ্চ ১০ এমবি
        ]);

        $path = $request->file('audio')->getRealPath();
        $mimeType = $request->file('audio')->getMimeType();

        $text = $this->aiService->transcribeAudio($path, $mimeType);

        return response()->json([
            'status' => true,
            'text' => $text
        ]);
    }

    /**
     * ৫. এআই স্মার্ট ডেটা এন্ট্রি (প্রাকৃতিক ভাষা থেকে রেজাল্ট ইনসার্ট)
     */
    
    public function smartDataEntry(Request $request)
    {
        $request->validate([
            'instruction' => 'required|string', 
            // যেমন: "মাওলানা আব্দুল্লাহকে গণিত বিষয়ে লিখিত ৭৫ এবং মৌখিক ২০ নম্বর দাও" (পরীক্ষার নাম বা রোল ছাড়াও কাজ করবে!)
        ]);

    

        $prompt = "নিচের বাক্যটি থেকে ছাত্রের আইডি বা রোল (যদি থাকে), ছাত্রের নাম, পরীক্ষার নাম (যদি উল্লেখ থাকে), বিষয়, লিখিত নম্বর এবং মৌখিক নম্বর আলাদা করো। যদি পরীক্ষার নাম বা আইডি না থাকে তবে সেগুলোর মান null বা ফাঁকা রাখবে। অন্য কোনো কথা বা ব্যাকটিক্স (```) ব্যবহার করবে না। 
        বাক্য: '{$request->instruction}'
                
        সঠিক JSON স্ট্রাকচার:
        {
        \"identifier\": \"...\",
        \"student_name\": \"...\",
        \"exam_name\": null,
        \"subject_name\": \"...\",
        \"written_marks\": 0,
        \"oral_marks\": 0
        }";

        $aiResponse = $this->aiService->generateText($prompt);
        
        $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
        $data = json_decode($cleanJson, true);

        if (!$data || !isset($data['student_name'])) {
            return response()->json([
                'status' => false, 
                'message' => 'এআই প্রদত্ত বাক্য থেকে ডেটা বুঝতে পারেনি।',
                'ai_response' => $aiResponse
            ], 400);
        }

        // ১. পরীক্ষার নাম হ্যান্ডেল করার লজিক (শিক্ষক না বললে সর্বশেষ বা ডিফল্ট পরীক্ষা ধরে নেবে)
        $rawExamName = $data['exam_name'] ?? null;
        if (empty($rawExamName)) {
            // যদি পরীক্ষার নাম না বলে, তবে ডেটাবেজ থেকে সর্বশেষ পরীক্ষার নামটি নিয়ে নেবে, না থাকলে বর্তমান বছরের বার্ষিক পরীক্ষা ধরবে
            $latestExam = ExamResult::latest()->value('exam_name');
            $examName = $latestExam ?? ('বার্ষিক পরীক্ষা ' . date('Y'));
        } else {
            $examBaseName = trim(preg_replace('/\b(202\d|২০২৬|২০২৫|২০২৭)\b/u', '', $rawExamName));
            $examName = $examBaseName . ' ' . date('Y');
        }

        // ২. ছাত্র খোঁজার স্মার্ট লজিক (আইডি, রোল কিংবা নাম দিয়ে)
        $identifier = $data['identifier'] ?? null;
        $studentName = $data['student_name'] ?? null;
        $numericId = $identifier ? preg_replace('/[^0-9]/', '', $identifier) : null;

        $students = collect();

        if (!empty($numericId)) {
            $query = User::query();
            $query->where(function($q) use ($numericId) {
                $q->where('id', $numericId);
                if (\Schema::hasColumn('users', 'roll')) {
                    $q->orWhere('roll', $numericId);
                }
            });
            $students = $query->get();
        } 
        
        // যদি আইডি দিয়ে না পাওয়া যায় বা আইডি দেওয়া না থাকে, তবে নাম দিয়ে খুঁজবে
        if ($students->isEmpty() && !empty($studentName)) {
            $students = User::where('name', 'LIKE', '%' . $studentName . '%')->get();
        }

        // ৩. আপনার কাঙ্ক্ষিত কন্ডিশন:
        if ($students->count() === 1) {
            // যদি মাত্র ১ জন পাওয়া যায়, সরাসরি নিয়ে নেবে (আইডি বলার দরকার নেই!)
            $student = $students->first();
        } elseif ($students->count() > 1) {
            // যদি একাধিক পাওয়া যায়, তবে আইডি বা রোল চাইতে বলবে
            $list = $students->map(fn($s) => "আইডি: {$s->id} ({$s->name})")->implode(', ');
            return response()->json([
                'status' => false, 
                'message' => "সতর্কতা: '{$studentName}' নামের একাধিক ছাত্র পাওয়া গেছে: [{$list}]। দয়া করে নির্দিষ্ট ছাত্রের আইডি বা রোল উল্লেখ করুন।"
            ], 422);
        } else {
            return response()->json([
                'status' => false, 
                'message' => "নির্দেশ অনুযায়ী কোনো ছাত্র খুঁজে পাওয়া যায়নি।"
            ], 404);
        }

        $written = $data['written_marks'] ?? 0;
        $oral = $data['oral_marks'] ?? 0;
        $total = $written + $oral;

        // গ্রেড নির্ধারণ
        $grade = 'F';
        if ($total >= 80) $grade = 'A+';
        elseif ($total >= 70) $grade = 'A';
        elseif ($total >= 60) $grade = 'A-';
        elseif ($total >= 50) $grade = 'B';
        elseif ($total >= 40) $grade = 'C';
        elseif ($total >= 33) $grade = 'D';

        // ডেটাবেজে আপডেট বা ইনসার্ট করা
        $examResult = ExamResult::updateOrCreate(
            [
                'student_id'   => $student->id,
                'exam_name'    => $examName,
                'subject_name' => $data['subject_name'],
            ],
            [
                'written_marks' => $written,
                'oral_marks'    => $oral,
                'total_marks'   => $total,
                'grade'         => $grade,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'এআই সফলভাবে ডেটাবেজে রেজাল্ট এন্ট্রি বা আপডেট করেছে!',
            'student_name' => $student->name,
            'exam_name' => $examName,
            'data' => $examResult
        ]);
    }

    /**
     * ৬. এআই স্মার্ট ফি/বেতন ডেটা এন্ট্রি (প্রাকৃতিক ভাষা থেকে বিল বা ফি ইনসার্ট)
     */
    // public function smartFeeEntry(Request $request)
    // {
    //     $request->validate([
    //         'instruction' => 'required|string', 
    //         // যেমন: "রোল ১ এর মাওলানা আব্দুল্লাহর জানুয়ারি মাসের মাসিক বেতন ১০০০ এবং লাইব্রেরি ফি ২০০ টাকা এন্ট্রি করো"
    //     ]);

    //     // সিকিউরিটি চেক: শুধুমাত্র অ্যাডমিন (অথবা নির্দিষ্ট রোল) কি না যাচাই করা
    //     $user = $request->user(); // বর্তমান লগইন করা ইউজার
        
    //     if (!$user || $user->role !== 'admin') {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'দুঃখিত! এই কাজটি করার জন্য আপনার কোনো অনুমতি নেই। শুধুমাত্র অ্যাডমিন এটি করতে পারবেন।'
    //         ], 403); // 403 Forbidden Error
    //     }

    //     $prompt = "নিচের বাক্যটি থেকে ছাত্রের আইডি বা রোল (যদি থাকে), ছাত্রের নাম, ফি-এর খাত বা শিরোনাম (যেমন: মাসিক বেতন, ভর্তি ফি, আবাসিক ফি ইত্যাদি), টাকার পরিমাণ এবং মাস (যদি উল্লেখ থাকে) আলাদা করো। যদি আইডি বা রোল না থাকে তবে identifier এর মান null রাখবে। অন্য কোনো কথা বা ব্যাকটিক্স (```) ব্যবহার করবে না। 
    //     বাক্য: '{$request->instruction}'
                
    //     সঠিক JSON স্ট্রাকচার:
    //     {
    //     \"identifier\": \"null বা রোল/আইডি\",
    //     \"student_name\": \"ছাত্রের নাম\",
    //     \"fee_title\": \"ফি এর নাম বা খাত\",
    //     \"amount\": 0,
    //     \"month\": \"মাস বা সময় (যেমন: জানুয়ারি ২০২৬)\"
    //     }";

    //     $aiResponse = $this->aiService->generateText($prompt);
        
    //     $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
    //     $data = json_decode($cleanJson, true);

    //     if (!$data || !isset($data['student_name'])) {
    //         return response()->json([
    //             'status' => false, 
    //             'message' => 'এআই প্রদত্ত বাক্য থেকে ফি-এর ডেটা বুঝতে পারেনি।',
    //             'ai_response' => $aiResponse
    //         ], 400);
    //     }

    //     // ছাত্র খোঁজার ইউনিভার্সাল লজিক (আইডি, রোল কিংবা নাম দিয়ে)
    //     $identifier = $data['identifier'] ?? null;
    //     $studentName = $data['student_name'] ?? null;
    //     $numericId = $identifier ? preg_replace('/[^0-9]/', '', $identifier) : null;

    //     $students = collect();

    //     if (!empty($numericId)) {
    //         $query = User::query();
    //         $query->where(function($q) use ($numericId) {
    //             $q->where('id', $numericId);
    //             if (\Schema::hasColumn('users', 'roll')) {
    //                 $q->orWhere('roll', $numericId);
    //             }
    //         });
    //         $students = $query->get();
    //     } 
        
    //     if ($students->isEmpty() && !empty($studentName)) {
    //         $students = User::where('name', 'LIKE', '%' . $studentName . '%')->get();
    //     }

    //     // ছাত্র নির্বাচন বা একাধিক থাকলে সতর্কতা
    //     if ($students->count() === 1) {
    //         $student = $students->first();
    //     } elseif ($students->count() > 1) {
    //         $list = $students->map(fn($s) => "আইডি: {$s->id} ({$s->name})")->implode(', ');
    //         return response()->json([
    //             'status' => false, 
    //             'message' => "সতর্কতা: '{$studentName}' নামের একাধিক ছাত্র পাওয়া গেছে: [{$list}]। দয়া করে নির্দিষ্ট ছাত্রের আইডি বা রোল উল্লেখ করুন।"
    //         ], 422);
    //     } else {
    //         return response()->json([
    //             'status' => false, 
    //             'message' => "নির্দেশ অনুযায়ী কোনো ছাত্র খুঁজে পাওয়া যায়নি।"
    //         ], 404);
    //     }

    //    $feeTitle = $data['fee_title'] ?? 'মাসিক বেতন';
    //     $amount = $data['amount'] ?? 0;
    //     $month = $data['month'] ?? date('F Y'); // মাস বা সময়

    //     // ডেটাবেজে ফি ইনসার্ট বা আপডেট করা (সঠিক কলাম: fee_title এবং due_date ব্যবহার করে)
    //     $feeRecord = Fee::updateOrCreate(
    //         [
    //             'student_id' => $student->id,
    //             'fee_title'  => $feeTitle . ' (' . $month . ')', // ফি-এর সাথে মাস যুক্ত করে টাইটেল তৈরি
    //         ],
    //         [
    //             'amount'      => $amount,
    //             'paid_amount' => 0,
    //             'status'      => 'unpaid', // ডিফল্টভাবে বকেয়া থাকবে
    //             'due_date'    => now()->format('Y-m-d'), // বর্তমান তারিখ বা নির্ধারিত ডেডলাইন
    //         ]
    //     );

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'এআই সফলভাবে ছাত্রের ফি ডেটাবেজে এন্ট্রি করেছে!',
    //         'student_name' => $student->name,
    //         'data' => $feeRecord
    //     ]);
    // }


    /**
     * ৬. এআই স্মার্ট ফি/বেতন ডেটা এন্ট্রি (প্রাকৃতিক ভাষা থেকে বিল বা ফি ইনসার্ট)
     */
    public function smartFeeEntry(Request $request)
    {
        $request->validate([
            'instruction' => 'required|string', 
        ]);

        // সিকিউরিটি চেক: শুধুমাত্র অ্যাডমিন কি না যাচাই করা
        $user =$request->user();
        
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'দুঃখিত! এই কাজটি করার জন্য আপনার কোনো অনুমতি নেই। শুধুমাত্র অ্যাডমিন এটি করতে পারবেন।'
            ], 403);
        }

        $prompt = "নিচের বাক্যটি থেকে ছাত্রের আইডি বা রোল (যদি থাকে), ছাত্রের নাম, ফি-এর খাত বা শিরোনাম (যেমন: মাসিক বেতন, ভর্তি ফি, আবাসিক ফি ইত্যাদি), টাকার পরিমাণ এবং মাস (যদি উল্লেখ থাকে) আলাদা করো। যদি আইডি বা রোল না থাকে তবে identifier এর মান null রাখবে। অন্য কোনো কথা বা ব্যাকটিক্স (```) ব্যবহার করবে না। 
        বাক্য: '{$request->instruction}'
                
        সঠিক JSON স্ট্রাকচার:
        {
        \"identifier\": \"null বা রোল/আইডি\",
        \"student_name\": \"ছাত্রের নাম\",
        \"fee_title\": \"ফি এর নাম বা খাত\",
        \"amount\": 0,
        \"month\": \"মাস বা সময় (যেমন: জানুয়ারি ২০২৬)\"
        }";

        $aiResponse = $this->aiService->generateText($prompt);
        
        $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
        $data = json_decode($cleanJson, true);

        if (!$data || !isset($data['student_name'])) {
            return response()->json([
                'status' => false, 
                'message' => 'এআই প্রদত্ত বাক্য থেকে ফি-এর ডেটা বুঝতে পারেনি।',
                'ai_response' => $aiResponse
            ], 400);
        }

        // ছাত্র খোঁজার ইউনিভার্সাল লজিক
        $identifier = $data['identifier'] ?? null;
        $studentName = $data['student_name'] ?? null;
        $numericId = $identifier ? preg_replace('/[^0-9]/', '', $identifier) : null;

        $students = collect();

        if (!empty($numericId)) {
            $query = User::query();
            $query->where(function($q) use ($numericId) {
                $q->where('id', $numericId);
                if (\Schema::hasColumn('users', 'roll')) {
                    $q->orWhere('roll', $numericId);
                }
            });
            $students = $query->get();
        } 
        
        if ($students->isEmpty() && !empty($studentName)) {
            $students = User::where('name', 'LIKE', '%' . $studentName . '%')->get();
        }

        // ছাত্র নির্বাচন বা একাধিক থাকলে সতর্কতা
        if ($students->count() === 1) {
            $student = $students->first();
        } elseif ($students->count() > 1) {
            $list = $students->map(fn($s) => "আইডি: {$s->id} ({$s->name})")->implode(', ');
            return response()->json([
                'status' => false, 
                'message' => "সতর্কতা: '{$studentName}' নামের একাধিক ছাত্র পাওয়া গেছে: [{$list}]। দয়া করে নির্দিষ্ট ছাত্রের আইডি বা রোল উল্লেখ করুন।"
            ], 422);
        } else {
            return response()->json([
                'status' => false, 
                'message' => "নির্দেশ অনুযায়ী কোনো ছাত্র খুঁজে পাওয়া যায়নি।"
            ], 404);
        }

        // ---------------------------------------------------------
        // এখানে পরিবর্তন করা হয়েছে: users টেবিলের আইডির বিপরীতে 
        // students টেবিল থেকে সঠিক student_id বের করে নেওয়া হচ্ছে
        // ---------------------------------------------------------
        $studentRecord = \App\Models\Student::where('user_id', $student->id)->first(); // অথবা \App\Models\Student
        
        // সঠিক মডেল পাথ ব্যবহার নিশ্চিত করতে নিচের লাইনটি ব্যবহার করুন:
        $studentRecord = \App\Models\Student::where('user_id', $student->id)->first();

        if (!$studentRecord) {
            return response()->json([
                'status' => false,
                'message' => "এই ইউজারের বিপরীতে কোনো ছাত্রের প্রাতিষ্ঠানিক প্রোফাইল পাওয়া যায়নি।"
            ], 404);
        }

        $feeTitle = $data['fee_title'] ?? 'মাসিক বেতন';
        $amount = $data['amount'] ?? 0;
        $month = $data['month'] ?? date('F Y');

        // ডেটাবেজে ফি ইনসার্ট বা আপডেট করা (students টেবিলের সঠিক id ব্যবহার করে)
        $feeRecord = Fee::updateOrCreate(
            [
                'student_id' => $studentRecord->id, // এখানে students টেবিলের id দেওয়া হলো
                'fee_title'  => $feeTitle . ' (' . $month . ')',
            ],
            [
                'amount'      => $amount,
                'paid_amount' => 0,
                'status'      => 'unpaid',
                'due_date'    => now()->format('Y-m-d'),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'এআই সফলভাবে ছাত্রের ফি ডেটাবেজে এন্ট্রি করেছে!',
            'student_name' => $student->name,
            'data' => $feeRecord
        ]);
    }

}