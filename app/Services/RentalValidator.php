<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Exceptions\RentalException;
use Carbon\CarbonInterface;

/**
 * Aturan validasi sewa baru: plat, kapasitas listrik, kompatibilitas, dan batas hari.
 */
class RentalValidator
{
    /** Pemesanan paling jauh sekian hari dari hari ini. */
    public const MAX_ADVANCE_DAYS = 60;

    public function __construct(private readonly RentalPriceCalculator $calculator) {}

    /**
     * Tanggal mulai tidak boleh sebelum hari ini dan tidak lebih jauh dari batas pemesanan di muka.
     *
     * @throws RentalException jika tanggal mulai di luar rentang yang diizinkan
     */
    public function validateStartDate(CarbonInterface $startDate, CarbonInterface $today): void
    {
        if ($startDate->copy()->startOfDay()->lt($today->copy()->startOfDay())) {
            throw new RentalException('Tanggal mulai sewa tidak boleh sebelum hari ini.');
        }

        if ($startDate->copy()->startOfDay()->gt($today->copy()->startOfDay()->addDays(self::MAX_ADVANCE_DAYS))) {
            throw new RentalException('Pemesanan paling jauh '.self::MAX_ADVANCE_DAYS.' hari dari hari ini.');
        }
    }

    /**
     * `$startDate` adalah tanggal mulai sewa; batas sewa harian mengikuti bulan tanggal tersebut.
     *
     * @throws RentalException jika ada aturan yang dilanggar
     */
    public function validate(
        VehicleType $vehicleType,
        ?string $plateNumber,
        ?float $batteryKwh,
        GarageType $garageType,
        RentalType $rentalType,
        ?int $days,
        CarbonInterface $startDate,
    ): void {
        if ($vehicleType->requiresPlate() && blank($plateNumber)) {
            throw new RentalException('Nomor plat wajib diisi kecuali untuk Sepeda.');
        }

        if ($vehicleType->requiresBattery() && ($batteryKwh === null || $batteryKwh <= 0)) {
            throw new RentalException('Kapasitas listrik (kWh) wajib diisi untuk Mobil Listrik.');
        }

        if (! in_array($vehicleType, $garageType->allowedVehicleTypes(), true)) {
            throw new RentalException("Kendaraan tersebut tidak dapat ditempatkan di {$garageType->label()}.");
        }

        if ($rentalType === RentalType::Daily) {
            $max = $this->calculator->maxDailyDays($startDate);

            if ($days === null || $days < 1 || $days > $max) {
                throw new RentalException("Sewa harian harus 1 sampai {$max} hari pada bulan tanggal mulai.");
            }
        }
    }
}
