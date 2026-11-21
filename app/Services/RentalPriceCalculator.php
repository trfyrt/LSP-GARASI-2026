<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use Carbon\CarbonInterface;

/**
 * Kalkulasi tanggal dan biaya sewa (tanpa akses database).
 */
class RentalPriceCalculator
{
    /** Harga sewa bulanan = 25 x harga harian. */
    public const MONTHLY_MULTIPLIER = 25;

    /** Sewa harian maksimal = jumlah hari bulan berjalan - 1 (Nov = 29, Feb = 27). */
    public function maxDailyDays(CarbonInterface $date): int
    {
        return $date->daysInMonth - 1;
    }

    /** Lama sewa: bulanan mengikuti jumlah hari bulan saat disewa, harian sesuai input. */
    public function durationDays(RentalType $type, ?int $days, CarbonInterface $start): int
    {
        return $type === RentalType::Monthly ? $start->daysInMonth : (int) $days;
    }

    /** Tanggal selesai (inklusif): mulai + lama sewa - 1 hari. */
    public function endDate(CarbonInterface $start, int $durationDays): CarbonInterface
    {
        return $start->copy()->addDays($durationDays - 1);
    }

    /** Biaya sewa pokok untuk satu periode. */
    public function rentFee(RentalType $type, int $dailyPrice, int $durationDays): int
    {
        return $type === RentalType::Monthly
            ? self::MONTHLY_MULTIPLIER * $dailyPrice
            : $dailyPrice * $durationDays;
    }

    /**
     * Biaya isi listrik = kWh kendaraan x harga per kWh.
     * Hanya mobil listrik yang ditempatkan di garasi mobil listrik.
     */
    public function chargingFee(GarageType $garageType, VehicleType $vehicleType, ?float $batteryKwh, ?int $chargingPrice): int
    {
        if ($garageType !== GarageType::ElectricCar || $vehicleType !== VehicleType::ElectricCar) {
            return 0;
        }

        return (int) round(($batteryKwh ?? 0) * ($chargingPrice ?? 0));
    }

    /** Biaya tambahan perpanjangan = harga harian x selisih hari. */
    public function extensionFee(int $dailyPrice, int $extraDays): int
    {
        return $dailyPrice * $extraDays;
    }
}
