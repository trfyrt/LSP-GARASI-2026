<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Membuat satu akun Admin awal (ganti password setelah login pertama).
 * Admin hanya punya baris di tabel `user`, tanpa profil `renter`.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@garasi.test'],
            [
                'name' => 'Administrator',
                'password' => '12345678',
                'role' => UserRole::Admin,
            ],
        );
    }
}
