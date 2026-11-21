<?php

namespace App\Models;

use App\Enums\RentalType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Transaksi sewa garasi beserta rincian biaya dan bukti bayar.
 */
#[Fillable([
    'user_id', 'garage_id', 'vehicle_id', 'rental_type', 'duration_days',
    'start_date', 'end_date', 'rent_fee', 'charging_fee', 'total_fee', 'payment_proof_path',
])]
class Rental extends Model
{
    protected function casts(): array
    {
        return [
            'rental_type' => RentalType::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** Riwayat perpanjangan sewa. */
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }
}
