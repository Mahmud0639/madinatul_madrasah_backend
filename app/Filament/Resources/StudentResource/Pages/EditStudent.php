<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // ১. সংশ্লিষ্ট User টেবিলের তথ্য নির্ভুলভাবে আপডেট করা
        if (isset($data['user'])) {
            $userData = $data['user'];
            
            $updateData = [
                'name' => $userData['name'] ?? $record->user->name,
                'phone' => $userData['phone'] ?? $record->user->phone,
                'email' => $userData['email'] ?? $record->user->email,
            ];

            // যদি নতুন পাসওয়ার্ড দেওয়া হয়, তবেই কেবল হ্যাশ করে আপডেট হবে
            if (!empty($userData['password'])) {
                $updateData['password'] = Hash::make($userData['password']);
            }

            // ইউজারের টেবিল আপডেট করা
            if ($record->user) {
                $record->user()->update($updateData);
            }

            unset($data['user']);
        }

        // ২. মূল স্টুডেন্ট রেকর্ড আপডেট করা
        $record->update($data);

        return $record;
    }
}