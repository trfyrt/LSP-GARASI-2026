<?php

namespace App\Models;

use App\Enums\RentalType;
use Carbon\CarbonImmutable;
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

    /**
     * Tipe sewa (harian atau bulanan) dibaca sebagai enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rental_type' => RentalType::class,
        ];
    }

    /** Penyewa yang melakukan sewa ini. */
    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'id_renter');
    }

    /** Garasi yang disewa. */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'id_garage');
    }

    /** Kendaraan yang ditempatkan pada garasi. */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'id_vehicle');
    }

    /** Invoice sewa awal dan perpanjangan. */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'id_rental');
    }

    /** Tanggal mulai sewa (invoice paling awal). */
    public function startDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->invoices->min('start_date'))->startOfDay();
    }

    /** Tanggal selesai sewa saat ini (invoice paling akhir, termasuk perpanjangan). */
    public function endDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->invoices->max('end_date'))->startOfDay();
    }

    /** Total seluruh biaya (sewa awal + perpanjangan). */
    public function totalFee(): int
    {
        return (int) $this->invoices->sum('total_fee');
    }
}
