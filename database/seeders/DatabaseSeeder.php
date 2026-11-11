<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ১. অ্যাডমিন ইউজার তৈরি (পোস্টম্যান টেস্ট ও ম্যানেজমেন্টের জন্য)
        User::updateOrCreate(
            ['email' => 'admin@madrasa.com'],
            [
                'name' => 'মাদ্রাসা অ্যাডমিন',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'phone' => '01711111111',
            ]
        );

        // ২. ছাত্রের কমন তথ্য `users` টেবিলে সেভ করা
        $userStudent = User::updateOrCreate(
            ['phone' => '01712345678'], // ফোন নম্বর দিয়ে চেক করবে যেন ডুপ্লিকেট না হয়
            [
                'name' => 'মাওলানা আব্দুল্লাহ',
                'password' => Hash::make('12345678'),
                'phone' => '01712345678',
                'role' => 'student',
            ]
        );

        // ৩. ছাত্রের মাদ্রাসার সুনির্দিষ্ট তথ্য `students` টেবিলে সেভ করা
        Student::updateOrCreate(
            ['user_id' => $userStudent->id], // ইউজার আইডি দিয়ে চেক করবে
            [
                'user_id' => $userStudent->id,
                'roll_number' => '1', // রোল ১
                'jamatu_class' => 'হিফজ বিভাগ', // আপনার পছন্দমতো জামাত বা ক্লাস দিতে পারেন
                'section' => 'residential',
                'date_of_birth' => '2010-01-01',
            ]
        );
    }
}