<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\InvoiceType;
use App\Exceptions\RentalException;
use App\Models\Garage;
use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Renter;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Alur sewa baru: validasi, hitung biaya, simpan bukti bayar, dan tandai garasi `used`.
 */
class RentalService
{
    public function __construct(
        private readonly RentalPriceCalculator $calculator,
        private readonly RentalValidator $validator,
        private readonly GarageAvailability $availability,
    ) {}

    /**
     * @throws RentalException jika penyewa belum Approved, garasi tidak tersedia/cocok, atau input melanggar aturan
     */
    public function rent(Renter $renter, NewRentalData $data, CarbonInterface $today): Rental
    {
        if (! $renter->canRent()) {
            throw new RentalException('Hanya penyewa yang sudah diverifikasi Admin yang dapat menyewa garasi.');
        }

        $path = null;

        try {
            return DB::transaction(function () use ($renter, $data, $today, &$path): Rental {
                $this->availability->releaseExpired($today);

                // Kunci garasi agar dua penyewa tidak mengambil garasi yang sama bersamaan.
                $garage = Garage::whereKey($data->garageId)->lockForUpdate()->first();

                if ($garage === null || $garage->status !== GarageStatus::Available) {
                    throw new RentalException('Garasi yang dipilih tidak tersedia.');
                }

                $plate = $data->vehicleType->requiresPlate() ? strtoupper(trim((string) $data->plateNumber)) : null;
                $kwh = $data->vehicleType->requiresBattery() ? $data->batteryKwh : null;

                $this->validator->validate(
                    $data->vehicleType, $plate, $kwh, $garage->type, $data->rentalType, $data->days, $today,
                );

                if ($plate !== null && $this->plateHasActiveRental($plate, $today)) {
                    throw new RentalException("Kendaraan dengan plat {$plate} masih memiliki sewa garasi yang berjalan.");
                }

                $duration = $this->calculator->durationDays($data->rentalType, $data->days, $today);
                $rentFee = $this->calculator->rentFee($data->rentalType, $garage->daily_price, $duration);
                $chargingFee = $this->calculator->chargingFee($garage->type, $data->vehicleType, $kwh, $garage->charging_price);

                $path = $data->paymentProof->store('payment-proofs', 'public');

                $vehicle = Vehicle::create([
                    'id_renter' => $renter->id,
                    'vehicle_type' => $data->vehicleType,
                    'plate_number' => $plate,
                    'brand_model' => $data->brandModel,
                    'battery_kwh' => $kwh,
                ]);

                $rental = Rental::create([
                    'id_renter' => $renter->id,
                    'id_garage' => $garage->id,
                    'id_vehicle' => $vehicle->id,
                    'rental_type' => $data->rentalType,
                ]);

                Invoice::create([
                    'id_rental' => $rental->id,
                    'type' => InvoiceType::Initial,
                    'start_date' => $today->toDateString(),
                    'end_date' => $this->calculator->endDate($today, $duration)->toDateString(),
                    'duration_days' => $duration,
                    'rent_fee' => $rentFee,
                    'charging_fee' => $chargingFee,
                    'total_fee' => $rentFee + $chargingFee,
                    'payment_proof_path' => $path,
                ]);

                $garage->update(['status' => GarageStatus::Used]);

                return $rental;
            });
        } catch (Throwable $e) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    /**
     * Satu kendaraan hanya boleh menempati satu garasi pada satu waktu.
     * Plat dibandingkan tanpa spasi dan huruf besar/kecil ("B 1234 XY" = "b1234xy").
     */
    private function plateHasActiveRental(string $plate, CarbonInterface $today): bool
    {
        $normalized = str_replace(' ', '', strtoupper($plate));

        return Rental::whereHas('vehicle', fn ($query) => $query
            ->whereRaw("UPPER(REPLACE(plate_number, ' ', '')) = ?", [$normalized]))
            ->whereHas('invoices', fn ($query) => $query->where('end_date', '>=', $today->toDateString()))
            ->exists();
    }
}
