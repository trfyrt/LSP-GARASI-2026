<?php

namespace App\Services;

use App\Enums\RentalType;
use App\Enums\VehicleType;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * Data masukan sewa baru dari penyewa.
 */
final class NewRentalData
{
    public function __construct(
        public readonly VehicleType $vehicleType,
        public readonly ?string $plateNumber,
        public readonly string $brandModel,
        public readonly ?float $batteryKwh,
        public readonly int $garageId,
        public readonly RentalType $rentalType,
        public readonly ?int $days,
        public readonly UploadedFile $paymentProof,
        /** Tanggal mulai sewa (hari ini atau di masa depan). */
        public readonly CarbonImmutable $startDate,
    ) {}
}
