<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user with specified email
        User::create([
            'name' => 'Michael Lowe',
            'email' => 'lowe.michael.nz@gmail.com',
            'email_verified_at' => now(),
            // If dev, we set a known password, else random
            'password' => env('APP_ENV') === 'local' ? Hash::make('password') : Hash::make(Str::random(16)),
            'remember_token' => Str::random(10),
            'is_admin' => true,
        ]);
    }
}