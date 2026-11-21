<?php

namespace App\Services;

use App\Enums\RenterStatus;
use App\Enums\UserRole;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mendaftarkan penyewa baru: membuat akun login (`user`) dan profil penyewa (`renter`).
 */
class RenterRegistrationService
{
    /**
     * Buat User (role Renter) dan Renter (status Pending) dalam satu transaksi,
     * sehingga tidak ada akun tanpa profil jika salah satu langkah gagal.
     *
     * @param  array{name: string, email: string, password: string, nik: string, phone_number: string}  $data
     */
    public function register(array $data): Renter
    {
        return DB::transaction(function () use ($data): Renter {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Renter,
            ]);

            return Renter::create([
                'id_user' => $user->id,
                'phone_number' => $data['phone_number'],
                'nik' => $data['nik'],
                'status' => RenterStatus::Pending,
            ]);
        });
    }
}
