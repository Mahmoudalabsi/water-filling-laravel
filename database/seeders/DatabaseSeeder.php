<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Settings;
use App\Models\Family;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('Test@2026'),
                'email_verified_at' => now(),
                'role' => 'FAMILY_MEMBER',
            ]
        );

        Settings::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'free_minutes_per_week' => 12,
                'price_per_minute' => 0.5,
                'electricity_tariff' => 0.55,
                'engine_power_kw' => 3.5,
            ]
        );

        Family::updateOrCreate(
            ['user_id' => $admin->id, 'name' => 'عائلة أحمد'],
            ['user_id' => $admin->id, 'name' => 'عائلة أحمد']
        );
    }
}
