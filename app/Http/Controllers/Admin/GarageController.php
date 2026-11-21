<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageBatchRequest;
use App\Models\Garage;
use App\Services\GarageBatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin mengelola garasi: melihat daftar dan membuat garasi secara batch.
 */
class GarageController extends Controller
{
    /** Daftar garasi (bisa difilter per tipe) beserta form pembuatan batch. */
    public function index(Request $request): View
    {
        $filter = GarageType::tryFrom((string) $request->query('type'));

        $garages = Garage::query()
            ->when($filter, fn ($query) => $query->where('type', $filter))
            ->orderBy('type')
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        $summary = [];
        foreach (Garage::selectRaw('type, status, COUNT(*) as total')->groupBy('type', 'status')->get() as $row) {
            $summary[$row->type->value][$row->status->value] = (int) $row->total;
        }

        return view('admin.garages.index', [
            'garages' => $garages,
            'filter' => $filter,
            'summary' => $summary,
            'types' => GarageType::cases(),
            'availableStatus' => GarageStatus::Available->value,
        ]);
    }

    /** Buat garasi batch dengan kode otomatis per tipe. */
    public function store(StoreGarageBatchRequest $request, GarageBatchService $service): RedirectResponse
    {
        $type = GarageType::from($request->validated('type'));

        $codes = $service->create(
            $type,
            (int) $request->validated('count'),
            (int) $request->validated('daily_price'),
            $request->filled('charging_price') ? (int) $request->validated('charging_price') : null,
        );

        $range = count($codes) === 1 ? $codes[0] : $codes[0].' - '.end($codes);

        return redirect()
            ->route('admin.garages.index')
            ->with('status', count($codes)." {$type->label()} berhasil dibuat ({$range}).");
    }
}
