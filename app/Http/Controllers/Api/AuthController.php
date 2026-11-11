<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // ইনপুটে ফোন নম্বর বা ইমেইল যেকোনো একটি এবং পাসওয়ার্ড বাধ্যতামূলক
        $request->validate([
            'login' => 'required|string', // এটি ফোন নম্বর বা ইমেইল উভয়ই হতে পারে
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');

        // ইনপুটটি ইমেইল ফরম্যাটে আছে কি না তা চেক করা
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // অথেন্টিকেশন চেক
        if (!Auth::attempt([$field => $loginInput, 'password' => $request->password])) {
            return response()->json([
                'message' => 'প্রদত্ত মোবাইল নম্বর/ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'লগইন সফল হয়েছে',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }
}
