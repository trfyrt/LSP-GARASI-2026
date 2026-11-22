<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Laporan penyewaan garasi. Setiap invoice (sewa baru maupun perpanjangan) dicatat
 * pada tanggal mulai periodenya. Filter: tahun, bulan (opsional), tipe garasi, penyewa.
 */
class RentalReportService
{
    /**
     * Rekap laporan untuk filter yang diberikan.
     *
     * @return array{
     *     totals: array{new: int, extension: int, rent_fee: int, charging_fee: int, total_fee: int},
     *     periods: list<array{label: string, new: int, extension: int, rent_fee: int, charging_fee: int, total_fee: int}>,
     *     by_type: list<array{label: string, new: int, extension: int, rent_fee: int, charging_fee: int, total_fee: int}>,
     *     by_renter: list<array{label: string, new: int, extension: int, rent_fee: int, charging_fee: int, total_fee: int}>
     * }
     */
    public function summarize(int $year, ?int $month, ?GarageType $type, ?int $renterId): array
    {
        $invoices = $this->filtered($year, $month, $type, $renterId)
            ->with(['rental.garage', 'rental.renter.user'])
            ->get();

        return [
            'totals' => $this->sum($invoices),
            'periods' => $month === null ? $this->byMonth($invoices, $year) : $this->byDay($invoices, $year, $month),
            'by_type' => $this->byType($invoices),
            'by_renter' => $this->byRenter($invoices),
        ];
    }

    /** Rincian transaksi (invoice) terbaru lebih dulu, dipaginasi. */
    public function details(int $year, ?int $month, ?GarageType $type, ?int $renterId, int $perPage = 25): LengthAwarePaginator
    {
        return $this->filtered($year, $month, $type, $renterId)
            ->with(['rental.garage', 'rental.renter.user', 'rental.vehicle'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Tahun yang memiliki transaksi, ditambah tahun berjalan; terbaru lebih dulu. */
    public function availableYears(CarbonImmutable $today): array
    {
        $years = Invoice::query()->pluck('start_date')
            ->map(fn ($date) => $date->year)
            ->push($today->year)
            ->unique()
            ->sortDesc()
            ->values();

        return $years->all();
    }

    /** @return Builder<Invoice> */
    private function filtered(int $year, ?int $month, ?GarageType $type, ?int $renterId): Builder
    {
        $start = CarbonImmutable::create($year, $month ?? 1, 1)->startOfDay();
        $end = $month === null ? $start->endOfYear() : $start->endOfMonth();

        return Invoice::query()
            ->whereDate('start_date', '>=', $start->toDateString())
            ->whereDate('start_date', '<=', $end->toDateString())
            ->when($type, fn (Builder $q) => $q->whereHas('rental.garage', fn (Builder $g) => $g->where('type', $type)))
            ->when($renterId, fn (Builder $q) => $q->whereHas('rental', fn (Builder $r) => $r->where('id_renter', $renterId)));
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array{new: int, extension: int, rent_fee: int, charging_fee: int, total_fee: int}
     */
    private function sum(Collection $invoices): array
    {
        return [
            'new' => $invoices->where('type', InvoiceType::Initial)->count(),
            'extension' => $invoices->where('type', InvoiceType::Extension)->count(),
            'rent_fee' => (int) $invoices->sum('rent_fee'),
            'charging_fee' => (int) $invoices->sum('charging_fee'),
            'total_fee' => (int) $invoices->sum('total_fee'),
        ];
    }

    /** @param Collection<int, Invoice> $invoices */
    private function byMonth(Collection $invoices, int $year): array
    {
        $grouped = $invoices->groupBy(fn (Invoice $i) => $i->start_date->month);

        return collect(range(1, 12))->map(fn (int $month): array => [
            'label' => CarbonImmutable::create($year, $month, 1)->translatedFormat('F'),
        ] + $this->sum($grouped->get($month, collect())))->all();
    }

    /** @param Collection<int, Invoice> $invoices */
    private function byDay(Collection $invoices, int $year, int $month): array
    {
        $grouped = $invoices->groupBy(fn (Invoice $i) => $i->start_date->day);
        $daysInMonth = CarbonImmutable::create($year, $month, 1)->daysInMonth;

        return collect(range(1, $daysInMonth))->map(fn (int $day): array => [
            'label' => CarbonImmutable::create($year, $month, $day)->translatedFormat('d F Y'),
        ] + $this->sum($grouped->get($day, collect())))->all();
    }

    /** @param Collection<int, Invoice> $invoices */
    private function byType(Collection $invoices): array
    {
        $grouped = $invoices->groupBy(fn (Invoice $i) => $i->rental->garage->type->value);

        return collect(GarageType::cases())->map(fn (GarageType $type): array => [
            'label' => $type->label(),
        ] + $this->sum($grouped->get($type->value, collect())))->all();
    }

    /** @param Collection<int, Invoice> $invoices */
    private function byRenter(Collection $invoices): array
    {
        return $invoices
            ->groupBy(fn (Invoice $i) => $i->rental->id_renter)
            ->map(fn (Collection $group): array => [
                'label' => $group->first()->rental->renter->user->name,
            ] + $this->sum($group))
            ->sortByDesc('total_fee')
            ->values()
            ->all();
    }
}
