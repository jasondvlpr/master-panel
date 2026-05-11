<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin
        User::create([
            'name' => 'Admin Panel',
            'email' => 'admin@domain.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create Developer
        User::create([
            'name' => 'Developer Account',
            'email' => 'dev@domain.com',
            'password' => Hash::make('password'),
            'role' => 'developer',
        ]);

        echo "Users created successfully!\n";
    }
}
