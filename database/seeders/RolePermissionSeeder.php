<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // পারমিশন ক্যাশ ক্লিয়ার করা
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ১. প্রয়োজনীয় পারমিশনসমূহ তৈরি করা
        Permission::create(['name' => 'create-diary']);
        Permission::create(['name' => 'view-diary']);
        Permission::create(['name' => 'create-fee']);
        Permission::create(['name' => 'view-fee']);
        Permission::create(['name' => 'create-result']);
        Permission::create(['name' => 'view-result']);

        // ২. রোলসমূহ তৈরি করা
        $adminRole = Role::create(['name' => 'admin']);
        $teacherRole = Role::create(['name' => 'teacher']);
        $studentRole = Role::create(['name' => 'student']);
        $parentRole = Role::create(['name' => 'parent']);

        // ৩. শিক্ষকের জন্য পারমিশন অ্যাসাইন করা (যেমন: ডায়েরি ও রেজাল্ট এন্ট্রি করা)
        $teacherRole->givePermissionTo([
            'create-diary', 
            'view-diary', 
            'create-result', 
            'view-result'
        ]);

        // ৪. অ্যাডমিনকে সিস্টেমের সব পারমিশন দিয়ে দেওয়া
        $adminRole->givePermissionTo(Permission::all());
    }
}