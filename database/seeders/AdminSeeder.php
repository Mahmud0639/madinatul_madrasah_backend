<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@madrasa.com'], // এই ইমেইল দিয়ে বারবার চেক করবে
            [
                'name' => 'মাদ্রাসা অ্যাডমিন',
                'password' => Hash::make('12345678'), // ডিফল্ট পাসওয়ার্ড
                'role' => 'admin',
            ]
        );
    }
}