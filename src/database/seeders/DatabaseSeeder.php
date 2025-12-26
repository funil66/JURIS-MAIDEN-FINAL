<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users and roles
        // Note: seeded passwords are for development purposes only and are hashed with bcrypt
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        $users = [
            [
                'name' => 'Allisson Gonçalves de Sousa',
                'email' => 'allisson@stofgard.com',
                'password' => 'Swordfish',
                'is_active' => true,
            ],
            [
                'name' => 'Maria de Jesus Silva',
                'email' => 'maria@stofgard.com',
                'password' => 'Stofgard',
                'is_active' => true,
            ],
            [
                'name' => 'Jaelsa Maria Silva',
                'email' => 'jaelsa@stofgard.com',
                'password' => 'Stofgard',
                'is_active' => true,
            ],
            [
                'name' => 'Raelcia Maria Silva',
                'email' => 'raelcia@stofgard.com',
                'password' => 'Stofgard',
                'is_active' => true,
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => bcrypt($u['password']),
                    'is_active' => $u['is_active'],
                ]
            );

            if ($user->email === 'allisson@stofgard.com') {
                $user->assignRole('admin');
            }
        }

        // Create placeholder for Google Drive integration settings
        $this->call(GoogleIntegrationSeeder::class);
    }
}
