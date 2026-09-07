<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'admin',
            'email' => 'zawedakhtar98@gmail.com',
            'password' => 'admin',
            'mobile_no' => '1234567890',
            'gender' => 'male',
            'role' => 'admin',
            'avatar' => 'admin.jpg',
            'is_active' => true,
        ]);
    }
}
