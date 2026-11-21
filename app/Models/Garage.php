<?php

namespace App\Models;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Garasi dengan kode unik per tipe (S###, C###, E###).
 */
#[Fillable(['code', 'type', 'daily_price', 'charging_price_per_kwh', 'status'])]
class Garage extends Model
{
    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'status' => GarageStatus::class,
        ];
    }

    /** Riwayat sewa garasi ini. */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
