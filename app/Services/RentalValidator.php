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
    public function __construct(private readonly RentalPriceCalculator $calculator) {}

    /**
     * @throws RentalException jika ada aturan yang dilanggar
     */
    public function validate(
        VehicleType $vehicleType,
        ?string $plateNumber,
        ?float $batteryKwh,
        GarageType $garageType,
        RentalType $rentalType,
        ?int $days,
        CarbonInterface $today,
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
            $max = $this->calculator->maxDailyDays($today);

            if ($days === null || $days < 1 || $days > $max) {
                throw new RentalException("Sewa harian harus 1 sampai {$max} hari pada bulan ini.");
            }
        }
    }
}
