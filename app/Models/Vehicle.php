<?php

namespace App\Models;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kendaraan penyewa. Plat wajib kecuali sepeda (BIKE); kapasitas kWh khusus mobil listrik.
 */
#[Fillable(['id_renter', 'vehicle_type', 'plate_number', 'brand_model', 'battery_kwh'])]
class Vehicle extends Model
{
    protected $table = 'vehicle';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'battery_kwh' => 'decimal:2',
        ];
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'id_renter');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'id_vehicle');
    }

    /** Apakah kendaraan ini boleh menempati garasi bertipe tertentu. */
    public function canBeParkedIn(GarageType $type): bool
    {
        return in_array($this->vehicle_type, $type->allowedVehicleTypes(), true);
    }
}
