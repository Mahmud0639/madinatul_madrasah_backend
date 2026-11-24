<?php

namespace App\Console\Commands;

use App\Models\LessonStudentStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLessonRemindersCommand extends Command
{
    protected $signature = 'lessons:send-reminders';
    protected $description = 'আজকের যেসব পড়া এখনো সম্পন্ন মার্ক করা হয়নি, সেগুলোর জন্য গার্ডিয়ান রিমাইন্ডার তৈরি/পাঠানো';

    public function handle(): int
    {
        $today = Carbon::now('Asia/Dhaka')->toDateString();

        $uncompleted = LessonStudentStatus::query()
            ->whereNull('completed_at')
            ->whereHas('lesson', fn ($q) => $q->whereDate('lesson_date', $today))
            ->with(['student.parent', 'lesson'])
            ->get();

        $count = 0;

        foreach ($uncompleted as $status) {
            $parent = $status->student?->parent;
            $lesson = $status->lesson;

            if (!$parent || !$lesson) {
                continue;
            }

            // এখানে আপনার FCM Push Notification বা SMS সার্ভিস কল করতে পারবেন
            // যেমন: app(PushNotificationService::class)->send($parent, 'পড়ার রিমাইন্ডার', ...);
            $count++;
        }

        $this->info("মোট {$count}টি অসম্পন্ন পড়ার জন্য রিমাইন্ডার প্রসেস করা হয়েছে।");

        return self::SUCCESS;
    }
}