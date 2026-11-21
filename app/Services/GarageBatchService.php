<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Support\Facades\DB;

/**
 * Admin membuat garasi secara berkelompok (batch) untuk satu tipe.
 */
class GarageBatchService
{
    public function __construct(private readonly GarageIdGenerator $ids) {}

    /**
     * Buat `$count` garasi berstatus Available dengan kode berurutan.
     * Harga isi listrik hanya disimpan untuk tipe mobil listrik.
     *
     * @return list<string> kode garasi yang dibuat, berurutan
     */
    public function create(GarageType $type, int $count, int $dailyPrice, ?int $chargingPrice): array
    {
        return DB::transaction(function () use ($type, $count, $dailyPrice, $chargingPrice): array {
            // Kunci baris terakhir tipe ini agar dua batch bersamaan tidak memakai nomor yang sama.
            $lastCode = Garage::where('type', $type)->orderByDesc('code')->lockForUpdate()->value('code');

            $codes = $this->ids->generate($type, $this->ids->numberFromCode($lastCode), $count);

            Garage::insert(array_map(fn (string $code): array => [
                'code' => $code,
                'type' => $type->value,
                'daily_price' => $dailyPrice,
                'charging_price' => $type->requiresChargingPrice() ? $chargingPrice : null,
                'status' => GarageStatus::Available->value,
            ], $codes));

            return $codes;
        });
    }
}
