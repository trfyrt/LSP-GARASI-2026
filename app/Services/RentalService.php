<?php

namespace App\Services;

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
 * Alur sewa baru (hari ini atau dipesan di muka): validasi, cek tabrakan tanggal,
 * hitung biaya, dan simpan bukti bayar.
 */
class RentalService
{
    public function __construct(
        private readonly RentalPriceCalculator $calculator,
        private readonly RentalValidator $validator,
        private readonly GarageAvailability $availability,
    ) {}

    /**
     * @throws RentalException jika penyewa belum Approved, tanggal mulai tidak valid, garasi bertabrakan,
     *                         atau input melanggar aturan
     */
    public function rent(Renter $renter, NewRentalData $data, CarbonInterface $today): Rental
    {
        if (! $renter->canRent()) {
            throw new RentalException('Hanya penyewa yang sudah diverifikasi Admin yang dapat menyewa garasi.');
        }

        $this->validator->validateStartDate($data->startDate, $today);

        $path = null;

        try {
            return DB::transaction(function () use ($renter, $data, $today, &$path): Rental {
                // Kunci garasi agar dua penyewa tidak memesan periode yang sama bersamaan.
                $garage = Garage::whereKey($data->garageId)->lockForUpdate()->first();

                if ($garage === null) {
                    throw new RentalException('Garasi yang dipilih tidak ditemukan.');
                }

                $start = $data->startDate;
                $plate = $data->vehicleType->requiresPlate() ? strtoupper(trim((string) $data->plateNumber)) : null;
                $kwh = $data->vehicleType->requiresBattery() ? $data->batteryKwh : null;

                $this->validator->validate(
                    $data->vehicleType, $plate, $kwh, $garage->type, $data->rentalType, $data->days, $start,
                );

                $duration = $this->calculator->durationDays($data->rentalType, $data->days, $start);
                $end = $this->calculator->endDate($start, $duration);

                if (! $this->availability->isFree($garage->id, $start, $end)) {
                    throw new RentalException('Garasi yang dipilih tidak tersedia pada periode tersebut.');
                }

                if ($plate !== null && $this->plateHasOverlappingRental($plate, $start, $end)) {
                    throw new RentalException("Kendaraan dengan plat {$plate} sudah memiliki sewa garasi yang bertabrakan dengan periode tersebut.");
                }

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
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'duration_days' => $duration,
                    'rent_fee' => $rentFee,
                    'charging_fee' => $chargingFee,
                    'total_fee' => $rentFee + $chargingFee,
                    'payment_proof_path' => $path,
                ]);

                $this->availability->syncStatuses($today);

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
     * Satu kendaraan hanya boleh berada di satu garasi pada satu waktu: periode sewa baru tidak boleh
     * beririsan dengan sewa lain untuk plat yang sama. Plat dibandingkan tanpa spasi dan huruf besar/kecil.
     */
    private function plateHasOverlappingRental(string $plate, CarbonInterface $start, CarbonInterface $end): bool
    {
        $normalized = str_replace(' ', '', strtoupper($plate));

        return Rental::whereHas('vehicle', fn ($query) => $query
            ->whereRaw("UPPER(REPLACE(plate_number, ' ', '')) = ?", [$normalized]))
            ->whereHas('invoices', fn ($query) => $query
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString()))
            ->exists();
    }
}
