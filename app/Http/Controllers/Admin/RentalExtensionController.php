<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageStatus;
use App\Exceptions\RentalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\GarageAvailability;
use App\Services\RentalExtensionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Admin memperpanjang sewa garasi yang sedang digunakan hari ini.
 */
class RentalExtensionController extends Controller
{
    /** Daftar garasi `used` hari ini beserta penyewa dan tanggal selesainya. */
    public function index(GarageAvailability $availability): View
    {
        $availability->releaseExpired(now()->startOfDay());

        $garages = Garage::where('status', GarageStatus::Used)
            ->with(['activeRental.renter.user', 'activeRental.vehicle', 'activeRental.invoices'])
            ->orderBy('code')
            ->get()
            ->filter(fn (Garage $garage): bool => $garage->activeRental !== null);

        return view('admin.extensions.index', ['garages' => $garages]);
    }

    /** Form perpanjangan satu sewa. */
    public function edit(Rental $rental): View
    {
        $rental->load(['garage', 'renter.user', 'vehicle', 'invoices']);

        abort_unless(
            $rental->garage->status === GarageStatus::Used && $rental->endDate()->gte(now()->startOfDay()),
            404,
        );

        return view('admin.extensions.edit', ['rental' => $rental]);
    }

    /** Simpan perpanjangan: invoice baru bertipe extension. */
    public function update(ExtendRentalRequest $request, Rental $rental, RentalExtensionService $service): RedirectResponse
    {
        $rental->load('garage');

        try {
            $invoice = $service->extend(
                $rental,
                CarbonImmutable::createFromFormat('Y-m-d', $request->validated('new_end_date'))->startOfDay(),
                $request->file('payment_proof'),
                now()->startOfDay(),
            );
        } catch (RentalException $e) {
            return back()->withInput()->withErrors(['new_end_date' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.extensions.index')
            ->with('status', "Sewa garasi {$rental->garage->code} diperpanjang sampai {$invoice->end_date->translatedFormat('d F Y')}.");
    }
}
