<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // delete old admin if exists
        User::where('email', 'sarita123@gmail.com')->delete();

        User::create([
            'name' => 'Admin User',
            'email' => 'sarita123@gmail.com',
            'password' => Hash::make('iamsaritaghimira'),
        ]);
    }
}
