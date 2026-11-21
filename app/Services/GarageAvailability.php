<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Models\Garage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Mengembalikan garasi berstatus `used` menjadi `available` setelah masa sewanya berakhir.
 */
class GarageAvailability
{
    /** Garasi yang tanggal selesai sewa terakhirnya sudah lewat dari `$today` dibebaskan. */
    public function releaseExpired(CarbonInterface $today): int
    {
        return Garage::where('status', GarageStatus::Used)
            ->whereNotExists(function ($query) use ($today): void {
                $query->select(DB::raw(1))
                    ->from('rental')
                    ->join('invoice', 'invoice.id_rental', '=', 'rental.id')
                    ->whereColumn('rental.id_garage', 'garage.id')
                    ->where('invoice.end_date', '>=', $today->toDateString());
            })
            ->update(['status' => GarageStatus::Available->value]);
    }
}
