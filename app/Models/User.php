<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Pengguna sistem: Admin atau Penyewa (email dipakai sebagai username).
 */
#[Fillable(['nik', 'name', 'phone', 'email', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /** Kendaraan milik penyewa. */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /** Transaksi sewa milik penyewa. */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Hanya penyewa berstatus Approved yang dapat menyewa. */
    public function canRent(): bool
    {
        return $this->role === UserRole::Renter && $this->status === UserStatus::Approved;
    }
}
