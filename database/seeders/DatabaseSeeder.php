<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $adminUid = (string) Str::uuid();
        Auth::create([
            'uid' => $adminUid,
            'email' => 'admin@corpman.com',
            'password_hash' => Hash::make('password'),
            'user_type' => 'admin',
            'status' => 'active',
            'is_email_verified' => true,
            'has_password' => true,
            'name' => 'Super Admin'
        ]);
        
        User::create([
            'uid' => $adminUid,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@corpman.com'
        ]);

        // Create Root User
        $rootUid = (string) Str::uuid();
        Auth::create([
            'uid' => $rootUid,
            'email' => 'root@corpman.com',
            'password_hash' => Hash::make('password'),
            'user_type' => 'root',
            'status' => 'active',
            'is_email_verified' => true,
            'has_password' => true,
            'name' => 'Root User'
        ]);
        
        User::create([
            'uid' => $rootUid,
            'first_name' => 'Root',
            'last_name' => 'User',
            'email' => 'root@corpman.com'
        ]);
    }
}
