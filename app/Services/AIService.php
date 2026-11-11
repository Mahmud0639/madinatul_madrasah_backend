<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $baseUrl;

   public function __construct()
{
    $this->apiKey = config('services.gemini.key');
    $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';
}

    /**
     * সাধারণ প্রম্পটের জন্য এআই রেসপন্স নেওয়া
     */
   public function generateText(string $prompt): string
{
    try {
        // timeout(30) যুক্ত করা হয়েছে যেন ১০ সেকেন্ডের বেশি লাগলেও ফেইল না করে
        $response = Http::timeout(30)->post("{$this->baseUrl}?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text') ?? 'কোনো উত্তর পাওয়া যায়নি।';
        }

        Log::error('Gemini API Error: ' . $response->body());
        return 'এআই সার্ভিসে সমস্যা হয়েছে। আবার চেষ্টা করুন।';
    } catch (\Exception $e) {
        Log::error('AIService Exception: ' . $e->getMessage());
        return 'সার্ভারে কিছু সমস্যা হয়েছে। এরর: ' . $e->getMessage(); // আসল এরর মেসেজটি রেসপন্সে দেখার জন্য
    }
}

    /**
     * অডিও ফাইল থেকে টেক্সটে রূপান্তর (Speech to Text)
     */
    public function transcribeAudio(string $audioPath, string $mimeType = 'audio/mp3'): string
    {
        try {
            $audioData = base64_encode(file_get_contents($audioPath));

            $response = Http::post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $audioData
                                ]
                            ],
                            [
                                'text' => 'এই অডিওটিতে যা বলা হয়েছে তা নিখুঁত বাংলা টেক্সটে রূপান্তর করো। অতিরিক্ত কোনো কথা বলবে না, শুধুমাত্র যা শোনা যাচ্ছে সেটাই লিখে দাও।'
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text') ?? '';
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Audio Transcription Error: ' . $e->getMessage());
            return '';
        }
    }
}