<?php

namespace App\Models;

use App\Enums\RentalType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kontrak sewa yang menghubungkan penyewa, garasi, dan kendaraan.
 * Tanggal dan biaya ada pada {@see Invoice}.
 */
#[Fillable(['id_renter', 'id_garage', 'id_vehicle', 'rental_type'])]
class Rental extends Model
{
    protected $table = 'rental';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'rental_type' => RentalType::class,
        ];
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'id_renter');
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'id_garage');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'id_vehicle');
    }

    /** Invoice sewa awal dan perpanjangan. */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'id_rental');
    }
}
