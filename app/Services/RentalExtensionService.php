<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\InvoiceType;
use App\Exceptions\RentalException;
use App\Models\Invoice;
use App\Models\Rental;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Admin memperpanjang sewa garasi yang sedang `used`: tanggal selesai baru,
 * biaya tambahan otomatis, dan bukti pembayaran tambahan (invoice bertipe extension).
 */
class RentalExtensionService
{
    public function __construct(
        private readonly RentalPriceCalculator $calculator,
        private readonly GarageAvailability $availability,
    ) {}

    /**
     * Sisa biaya tambahan jika tanggal selesai dimundurkan ke `$newEnd`.
     *
     * @throws RentalException jika `$newEnd` tidak melewati tanggal selesai saat ini
     */
    public function quote(Rental $rental, CarbonInterface $newEnd): int
    {
        return $this->calculator->extensionFee($rental->garage->daily_price, $this->extraDays($rental, $newEnd));
    }

    /**
     * @throws RentalException jika sewa sudah berakhir atau tanggal baru tidak melewati tanggal selesai saat ini
     */
    public function extend(Rental $rental, CarbonInterface $newEnd, UploadedFile $proof, CarbonInterface $today): Invoice
    {
        $path = null;

        try {
            return DB::transaction(function () use ($rental, $newEnd, $proof, $today, &$path): Invoice {
                $this->availability->releaseExpired($today);

                $rental = Rental::whereKey($rental->id)->with('garage')->lockForUpdate()->firstOrFail();

                if ($rental->garage->status !== GarageStatus::Used || $rental->endDate()->lt($today)) {
                    throw new RentalException('Sewa ini sudah berakhir dan tidak dapat diperpanjang.');
                }

                $currentEnd = $rental->endDate();
                $extraDays = $this->extraDays($rental, $newEnd);
                $fee = $this->calculator->extensionFee($rental->garage->daily_price, $extraDays);

                $path = $proof->store('payment-proofs', 'public');

                return Invoice::create([
                    'id_rental' => $rental->id,
                    'type' => InvoiceType::Extension,
                    'start_date' => $currentEnd->copy()->addDay()->toDateString(),
                    'end_date' => $newEnd->toDateString(),
                    'duration_days' => $extraDays,
                    'rent_fee' => $fee,
                    'charging_fee' => 0,
                    'total_fee' => $fee,
                    'payment_proof_path' => $path,
                ]);
            });
        } catch (Throwable $e) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    /** Selisih hari antara tanggal selesai baru dan saat ini; harus positif. */
    private function extraDays(Rental $rental, CarbonInterface $newEnd): int
    {
        $extraDays = (int) $rental->endDate()->startOfDay()->diffInDays($newEnd->copy()->startOfDay(), false);

        if ($extraDays < 1) {
            throw new RentalException('Tanggal selesai baru harus setelah tanggal selesai saat ini.');
        }

        return $extraDays;
    }
}
