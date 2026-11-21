<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Membuat satu akun Admin awal (ganti password setelah login pertama).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@garasi.test'],
            [
                'name' => 'Administrator',
                'password' => 'admin12345',
                'role' => UserRole::Admin,
                'status' => UserStatus::Approved,
            ],
        );
    }
}
