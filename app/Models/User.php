<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun login sistem: Admin atau Penyewa (email dipakai sebagai username).
 * Data khusus penyewa ada di {@see Renter}.
 */
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $table = 'user';

    public $timestamps = false;

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
        ];
    }

    /** Tabel `user` tidak punya kolom remember_token, jadi fitur "ingat saya" dimatikan. */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /** Profil penyewa (hanya ada untuk role Renter). */
    public function renter(): HasOne
    {
        return $this->hasOne(Renter::class, 'id_user');
    }

    /** Apakah pengguna berperan Admin. */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Hanya penyewa berstatus Approved yang dapat menyewa. */
    public function canRent(): bool
    {
        return $this->renter?->canRent() ?? false;
    }
}
