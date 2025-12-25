<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'devtester@example.com')->exists()) {
            User::create([
                'name' => 'Dev Tester',
                'email' => 'devtester@example.com',
                'password' => bcrypt('Secret123!'),
            ]);
        }
    }
}
