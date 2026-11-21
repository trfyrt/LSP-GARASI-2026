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
#[Fillable(['code', 'type', 'daily_price', 'charging_price', 'status'])]
class Garage extends Model
{
    protected $table = 'garage';

    public $timestamps = false;

    /** Garasi baru selalu berstatus tersedia. */
    protected $attributes = ['status' => 'AVAILABLE'];

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
        return $this->hasMany(Rental::class, 'id_garage');
    }

    public function isAvailable(): bool
    {
        return $this->status === GarageStatus::Available;
    }
}
