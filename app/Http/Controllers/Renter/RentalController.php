<?php

namespace App\Http\Controllers\Renter;

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
use App\Services\RentalValidator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Penyewa menyewa garasi (hari ini atau dipesan di muka) dan melihat daftar sewanya.
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

        return view('renter.rentals.index', ['rentals' => $rentals, 'today' => CarbonImmutable::now()->startOfDay()]);
    }

    /** Form sewa baru. Daftar garasi diambil dinamis sesuai tanggal mulai dan durasi. */
    public function create(Request $request): View
    {
        abort_unless($request->user()->renter?->canRent(), 403, 'Hanya penyewa yang sudah diverifikasi Admin yang dapat menyewa garasi.');

        $today = CarbonImmutable::now()->startOfDay();

        return view('renter.rentals.create', [
            'vehicleTypes' => VehicleType::cases(),
            'garageTypes' => GarageType::cases(),
            'allowed' => collect(GarageType::cases())->mapWithKeys(fn (GarageType $type): array => [
                $type->value => array_map(fn (VehicleType $v): string => $v->value, $type->allowedVehicleTypes()),
            ]),
            'today' => $today,
            'maxStart' => $today->addDays(RentalValidator::MAX_ADVANCE_DAYS),
            'monthlyMultiplier' => RentalPriceCalculator::MONTHLY_MULTIPLIER,
        ]);
    }

    /**
     * Garasi yang kosong selama periode sewa yang diminta (JSON untuk form sewa baru).
     * Parameter: start_date (default hari ini), rental_type, days.
     */
    public function availability(
        Request $request,
        GarageAvailability $availability,
        RentalPriceCalculator $calculator,
        RentalValidator $validator,
    ): JsonResponse {
        abort_unless($request->user()->renter?->canRent(), 403);

        $today = CarbonImmutable::now()->startOfDay();
        $startInput = (string) $request->query('start_date', '');
        $type = RentalType::tryFrom((string) $request->query('rental_type')) ?? RentalType::Daily;
        $days = $request->filled('days') ? (int) $request->query('days') : null;

        try {
            $start = $startInput === '' ? $today : CarbonImmutable::createFromFormat('!Y-m-d', $startInput);
            $validator->validateStartDate($start, $today);
        } catch (RentalException $e) {
            return response()->json(['message' => $e->getMessage(), 'garages' => []]);
        } catch (\Throwable) {
            return response()->json(['message' => 'Format tanggal mulai tidak valid.', 'garages' => []]);
        }

        $payload = [
            'start_date' => $start->toDateString(),
            'days_in_month' => $start->daysInMonth,
            'max_daily' => $calculator->maxDailyDays($start),
            'message' => null,
            'end_date' => null,
            'duration_days' => null,
            'garages' => [],
        ];

        if ($type === RentalType::Daily && ($days === null || $days < 1 || $days > $payload['max_daily'])) {
            $payload['message'] = $days === null ? null : "Sewa harian harus 1 sampai {$payload['max_daily']} hari pada bulan tanggal mulai.";

            return response()->json($payload);
        }

        $duration = $calculator->durationDays($type, $days, $start);
        $end = $calculator->endDate($start, $duration);

        $payload['end_date'] = $end->toDateString();
        $payload['duration_days'] = $duration;
        $payload['garages'] = $availability->availableGarages($start, $end)
            ->map(fn (Garage $garage): array => [
                'id' => $garage->id,
                'code' => $garage->code,
                'type' => $garage->type->value,
                'daily_price' => $garage->daily_price,
                'charging_price' => $garage->charging_price,
            ])->values()->all();

        return response()->json($payload);
    }

    /** Simpan sewa baru. Garasi menjadi `used` bila periodenya mencakup hari ini. */
    public function store(StoreRentalRequest $request, RentalService $service): RedirectResponse
    {
        $today = CarbonImmutable::now()->startOfDay();
        $start = $request->filled('start_date')
            ? CarbonImmutable::createFromFormat('!Y-m-d', $request->validated('start_date'))
            : $today;

        $data = new NewRentalData(
            VehicleType::from($request->validated('vehicle_type')),
            $request->validated('plate_number'),
            $request->validated('brand_model'),
            $request->filled('battery_kwh') ? (float) $request->validated('battery_kwh') : null,
            (int) $request->validated('id_garage'),
            RentalType::from($request->validated('rental_type')),
            $request->filled('days') ? (int) $request->validated('days') : null,
            $request->file('payment_proof'),
            $start,
        );

        try {
            $rental = $service->rent($request->user()->renter, $data, $today);
        } catch (RentalException $e) {
            return back()->withInput()->withErrors(['rental' => $e->getMessage()]);
        }

        $rental->load('garage');
        $message = $start->gt($today)
            ? "Pemesanan garasi {$rental->garage->code} mulai {$start->translatedFormat('d F Y')} berhasil disimpan."
            : "Sewa berhasil disimpan. Garasi {$rental->garage->code} kini berstatus digunakan.";

        return redirect()->route('rentals.index')->with('status', $message);
    }
}
