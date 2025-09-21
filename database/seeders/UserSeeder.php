<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@affordaily.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create receptionist user
        User::create([
            'name' => 'Receptionist User',
            'email' => 'receptionist@affordaily.com',
            'password' => Hash::make('receptionist123'),
            'role' => 'receptionist',
            'email_verified_at' => now(),
        ]);
    }
}
