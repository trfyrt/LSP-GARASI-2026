<?php

namespace App\Models;

use App\Enums\RenterStatus;
use Database\Factories\RenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil penyewa (NIK, telepon, status verifikasi). Relasi 1:1 ke {@see User}.
 */
#[Fillable(['id_user', 'phone_number', 'status', 'nik'])]
class Renter extends Model
{
    /** @use HasFactory<RenterFactory> */
    use HasFactory;

    protected $table = 'renter';

    public $timestamps = false;

    /** Default sama dengan default kolom di database. */
    protected $attributes = ['status' => 'PENDING'];

    protected function casts(): array
    {
        return [
            'status' => RenterStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /** Kendaraan milik penyewa. */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'id_renter');
    }

    /** Transaksi sewa milik penyewa. */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'id_renter');
    }

    /** Hanya penyewa berstatus Approved yang dapat menyewa. */
    public function canRent(): bool
    {
        return $this->status === RenterStatus::Approved;
    }

    /** Admin menerima atau menolak pendaftaran penyewa. */
    public function verify(bool $accept): void
    {
        $this->status = $accept ? RenterStatus::Approved : RenterStatus::Rejected;
        $this->save();
    }
}
