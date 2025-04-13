<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        User::create([
            'name' => 'Admin',
            'email' => 'admin@login.com',
            'phone' => '09199338144',
            'password' => bcrypt('Sdsd@1212'),
            'user_type' => 'admin',
            'remember_token' => 'Sdsd@1212',
            'email_verified_at' => now(),
        ]);


    }
}
