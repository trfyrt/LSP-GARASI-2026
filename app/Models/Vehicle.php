<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kendaraan penyewa. Plat wajib kecuali sepeda; kapasitas kWh khusus mobil listrik.
 */
#[Fillable(['user_id', 'type', 'plate_number', 'brand', 'model', 'battery_kwh'])]
class Vehicle extends Model
{
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'battery_kwh' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
