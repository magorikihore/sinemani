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
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Only create test accounts in non-production environments
        if (app()->isProduction()) {
            $this->command->warn('Skipping test user creation in production. Set ADMIN_PASSWORD in .env to create admin.');

            // Only create admin if ADMIN_PASSWORD is explicitly set
            $adminPassword = env('ADMIN_PASSWORD');
            $adminEmail = env('ADMIN_EMAIL', 'admin@dramabox.com');
            if ($adminPassword) {
                $admin = User::firstOrCreate(
                    ['email' => $adminEmail],
                    [
                        'name' => 'Admin',
                        'email' => $adminEmail,
                        'password' => bcrypt($adminPassword),
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]
                );
                $admin->assignRole('admin');
            }
        } else {
            // Development/staging: create test accounts
            $admin = User::firstOrCreate(
                ['email' => 'admin@dramabox.com'],
                [
                    'name' => 'Admin',
                    'email' => 'admin@dramabox.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $admin->assignRole('admin');

            $testUser = User::firstOrCreate(
                ['email' => 'user@dramabox.com'],
                [
                    'name' => 'Test User',
                    'email' => 'user@dramabox.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'coin_balance' => 100,
                    'is_active' => true,
                ]
            );
            $testUser->assignRole('user');
        }

        // Seed default data
        $this->call(DefaultDataSeeder::class);
    }
}
