<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\RentalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Models\Rental;
use App\Services\GarageAvailability;
use App\Services\RentalExtensionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Admin memperpanjang sewa garasi yang sedang berjalan hari ini.
 */
class RentalExtensionController extends Controller
{
    /** Daftar sewa yang periodenya mencakup hari ini (garasi yang sedang disewa), urut kode garasi. */
    public function index(GarageAvailability $availability): View
    {
        $today = CarbonImmutable::now()->startOfDay();
        $availability->syncStatuses($today);

        $rentals = Rental::query()
            ->whereHas('invoices', fn ($query) => $query
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString()))
            ->with(['garage', 'renter.user', 'vehicle', 'invoices'])
            ->get()
            ->sortBy(fn (Rental $rental) => $rental->garage->code)
            ->values();

        return view('admin.extensions.index', ['rentals' => $rentals]);
    }

    /** Form perpanjangan satu sewa. */
    public function edit(Rental $rental, GarageAvailability $availability): View
    {
        $rental->load(['garage', 'renter.user', 'vehicle', 'invoices']);

        abort_unless($rental->endDate()->gte(CarbonImmutable::now()->startOfDay()), 404);

        return view('admin.extensions.edit', [
            'rental' => $rental,
            'nextBooking' => $availability->nextBookingStart($rental->id_garage, $rental->endDate()),
        ]);
    }

    /** Simpan perpanjangan: invoice baru bertipe extension. */
    public function update(ExtendRentalRequest $request, Rental $rental, RentalExtensionService $service): RedirectResponse
    {
        $rental->load('garage');

        try {
            $invoice = $service->extend(
                $rental,
                CarbonImmutable::createFromFormat('!Y-m-d', $request->validated('new_end_date')),
                $request->file('payment_proof'),
                CarbonImmutable::now()->startOfDay(),
            );
        } catch (RentalException $e) {
            return back()->withInput()->withErrors(['new_end_date' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.extensions.index')
            ->with('status', "Sewa garasi {$rental->garage->code} diperpanjang sampai {$invoice->end_date->translatedFormat('d F Y')}.");
    }
}
