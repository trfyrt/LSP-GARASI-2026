<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Models\Garage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ketersediaan garasi berdasarkan rentang tanggal invoice (tanpa mengubah skema database).
 *
 * Sebuah garasi bebas pada periode A sampai B bila tidak ada invoice milik rental garasi itu
 * yang tanggalnya beririsan dengan A sampai B. Kolom `garage.status` hanya penanda "sedang
 * digunakan hari ini" dan disinkronkan lewat {@see syncStatuses()}.
 */
class GarageAvailability
{
    /** Garasi yang bebas penuh selama `$start` sampai `$end` (inklusif), urut kode. */
    public function availableGarages(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return Garage::query()
            ->whereNotExists(fn ($query) => $this->overlapping($query, $start, $end))
            ->orderBy('code')
            ->get(['id', 'code', 'type', 'daily_price', 'charging_price']);
    }

    /** Apakah garasi ini bebas penuh selama `$start` sampai `$end` (inklusif). */
    public function isFree(int $garageId, CarbonInterface $start, CarbonInterface $end): bool
    {
        return ! DB::table('garage')
            ->where('garage.id', $garageId)
            ->whereExists(fn ($query) => $this->overlapping($query, $start, $end))
            ->exists();
    }

    /** Tanggal mulai pemesanan terdekat setelah `$after` pada garasi ini, atau null bila tidak ada. */
    public function nextBookingStart(int $garageId, CarbonInterface $after): ?CarbonImmutable
    {
        $start = DB::table('rental')
            ->join('invoice', 'invoice.id_rental', '=', 'rental.id')
            ->where('rental.id_garage', $garageId)
            ->whereDate('invoice.start_date', '>', $after->toDateString())
            ->min('invoice.start_date');

        return $start === null ? null : CarbonImmutable::parse($start)->startOfDay();
    }

    /**
     * Sinkronkan `garage.status` dengan kondisi hari ini: USED bila ada invoice yang mencakup
     * hari ini, selain itu AVAILABLE. Pemesanan masa depan tidak membuat garasi USED.
     */
    public function syncStatuses(CarbonInterface $today): void
    {
        $covering = fn ($query) => $query->select(DB::raw(1))
            ->from('rental')
            ->join('invoice', 'invoice.id_rental', '=', 'rental.id')
            ->whereColumn('rental.id_garage', 'garage.id')
            ->whereDate('invoice.start_date', '<=', $today->toDateString())
            ->whereDate('invoice.end_date', '>=', $today->toDateString());

        Garage::where('status', GarageStatus::Available)->whereExists($covering)
            ->update(['status' => GarageStatus::Used->value]);

        Garage::where('status', GarageStatus::Used)->whereNotExists($covering)
            ->update(['status' => GarageStatus::Available->value]);
    }

    /** Subquery: invoice milik garasi yang beririsan dengan `$start` sampai `$end`. */
    private function overlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->select(DB::raw(1))
            ->from('rental')
            ->join('invoice', 'invoice.id_rental', '=', 'rental.id')
            ->whereColumn('rental.id_garage', 'garage.id')
            ->whereDate('invoice.start_date', '<=', $end->toDateString())
            ->whereDate('invoice.end_date', '>=', $start->toDateString());
    }
}
