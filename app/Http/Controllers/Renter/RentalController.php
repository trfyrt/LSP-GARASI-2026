<?php

namespace App\Http\Controllers\Renter;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Exceptions\RentalException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Garage;
use App\Services\GarageAvailability;
use App\Services\NewRentalData;
use App\Services\RentalPriceCalculator;
use App\Services\RentalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Penyewa menyewa garasi baru dan melihat daftar sewanya.
 */
class RentalController extends Controller
{
    /** Daftar sewa milik penyewa yang sedang login. */
    public function index(Request $request): View
    {
        $rentals = $request->user()->renter
            ->rentals()
            ->with(['garage', 'vehicle', 'invoices'])
            ->latest('id')
            ->get();

        return view('renter.rentals.index', ['rentals' => $rentals, 'today' => now()->startOfDay()]);
    }

    /** Form sewa baru beserta daftar garasi yang tersedia hari ini. */
    public function create(Request $request, GarageAvailability $availability, RentalPriceCalculator $calculator): View
    {
        abort_unless($request->user()->renter?->canRent(), 403, 'Hanya penyewa yang sudah diverifikasi Admin yang dapat menyewa garasi.');

        $today = now()->startOfDay();
        $availability->releaseExpired($today);

        $garages = Garage::where('status', GarageStatus::Available)
            ->orderBy('code')
            ->get(['id', 'code', 'type', 'daily_price', 'charging_price'])
            ->map(fn (Garage $garage): array => [
                'id' => $garage->id,
                'code' => $garage->code,
                'type' => $garage->type->value,
                'daily_price' => $garage->daily_price,
                'charging_price' => $garage->charging_price,
            ]);

        return view('renter.rentals.create', [
            'garages' => $garages,
            'vehicleTypes' => VehicleType::cases(),
            'garageTypes' => GarageType::cases(),
            'allowed' => collect(GarageType::cases())->mapWithKeys(fn (GarageType $type): array => [
                $type->value => array_map(fn (VehicleType $v): string => $v->value, $type->allowedVehicleTypes()),
            ]),
            'today' => $today,
            'daysInMonth' => $today->daysInMonth,
            'maxDaily' => $calculator->maxDailyDays($today),
            'monthlyMultiplier' => RentalPriceCalculator::MONTHLY_MULTIPLIER,
        ]);
    }

    /** Simpan sewa baru; garasi menjadi `used`. */
    public function store(StoreRentalRequest $request, RentalService $service): RedirectResponse
    {
        $data = new NewRentalData(
            VehicleType::from($request->validated('vehicle_type')),
            $request->validated('plate_number'),
            $request->validated('brand_model'),
            $request->filled('battery_kwh') ? (float) $request->validated('battery_kwh') : null,
            (int) $request->validated('id_garage'),
            RentalType::from($request->validated('rental_type')),
            $request->filled('days') ? (int) $request->validated('days') : null,
            $request->file('payment_proof'),
        );

        try {
            $rental = $service->rent($request->user()->renter, $data, now()->startOfDay());
        } catch (RentalException $e) {
            return back()->withInput()->withErrors(['rental' => $e->getMessage()]);
        }

        return redirect()
            ->route('rentals.index')
            ->with('status', 'Sewa berhasil disimpan. Garasi '.$rental->garage->code.' kini berstatus digunakan.');
    }
}
