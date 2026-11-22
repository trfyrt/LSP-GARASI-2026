<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Models\Renter;
use App\Services\RentalReportService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Admin melihat laporan penyewaan garasi dengan filter tahun, bulan, tipe garasi, dan penyewa.
 */
class ReportController extends Controller
{
    /** Tampilkan laporan penyewaan sesuai filter tahun, bulan, tipe garasi, dan penyewa. */
    public function index(ReportFilterRequest $request, RentalReportService $reports): View
    {
        $today = CarbonImmutable::now()->startOfDay();

        $year = (int) ($request->validated('year') ?? $today->year);
        $month = $request->filled('month') ? (int) $request->validated('month') : null;
        $type = GarageType::tryFrom((string) $request->validated('type'));
        $renterId = $request->filled('renter') ? (int) $request->validated('renter') : null;

        return view('admin.reports.index', [
            'report' => $reports->summarize($year, $month, $type, $renterId),
            'details' => $reports->details($year, $month, $type, $renterId),
            'year' => $year,
            'month' => $month,
            'type' => $type,
            'renterId' => $renterId,
            'years' => $reports->availableYears($today),
            'months' => collect(range(1, 12))->mapWithKeys(
                fn (int $m): array => [$m => CarbonImmutable::create($year, $m, 1)->translatedFormat('F')],
            ),
            'types' => GarageType::cases(),
            'renters' => Renter::with('user:id,name')->get()->sortBy(fn (Renter $r) => $r->user->name)->values(),
        ]);
    }
}
