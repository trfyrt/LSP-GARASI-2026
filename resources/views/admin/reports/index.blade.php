@extends('layouts.app')

@section('title', 'Laporan')

@section('content')
    @php
        $input = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500';
        $rp = fn (int $n): string => 'Rp '.number_format($n, 0, ',', '.');
        $periodTitle = $month === null ? "Per bulan, tahun {$year}" : 'Per hari, '.$months[$month]." {$year}";
        $hasFilter = $month !== null || $type !== null || $renterId !== null;
    @endphp

    <h1 class="text-2xl font-semibold text-slate-900">Laporan Penyewaan</h1>
    <p class="mt-1 text-sm text-slate-500">
        Setiap invoice (sewa baru dan perpanjangan) dicatat pada tanggal mulai periodenya.
    </p>

    <form method="GET" action="{{ route('admin.reports.index') }}"
        class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="year" class="mb-1 block text-sm font-medium">Tahun</label>
            <select id="year" name="year" class="{{ $input }}">
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="month" class="mb-1 block text-sm font-medium">Bulan (per hari)</label>
            <select id="month" name="month" class="{{ $input }}">
                <option value="">Semua bulan</option>
                @foreach ($months as $number => $name)
                    <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="type" class="mb-1 block text-sm font-medium">Tipe garasi</label>
            <select id="type" name="type" class="{{ $input }}">
                <option value="">Semua tipe</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}" @selected($type === $t)>{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="renter" class="mb-1 block text-sm font-medium">Penyewa</label>
            <select id="renter" name="renter" class="{{ $input }}">
                <option value="">Semua penyewa</option>
                @foreach ($renters as $r)
                    <option value="{{ $r->id }}" @selected($renterId === $r->id)>{{ $r->user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Tampilkan</button>
            @if ($hasFilter)
                <a href="{{ route('admin.reports.index', ['year' => $year]) }}"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Reset filter</a>
            @endif
        </div>
    </form>

    <div class="mt-6 grid gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Sewa baru</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $report['totals']['new'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Perpanjangan</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $report['totals']['extension'] }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Biaya sewa + listrik</div>
            <div class="mt-1 text-sm text-slate-700">{{ $rp($report['totals']['rent_fee']) }} + {{ $rp($report['totals']['charging_fee']) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Total pendapatan</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $rp($report['totals']['total_fee']) }}</div>
        </div>
    </div>

    @php
        $summaryTable = function (string $title, string $firstColumn, array $rows) use ($rp) {
            return compact('title', 'firstColumn', 'rows');
        };
        $tables = [
            $summaryTable($periodTitle, $month === null ? 'Bulan' : 'Tanggal', $report['periods']),
            $summaryTable('Per tipe garasi', 'Tipe garasi', $report['by_type']),
            $summaryTable('Per penyewa', 'Penyewa', $report['by_renter']),
        ];
    @endphp

    @foreach ($tables as $table)
        <section class="mt-8">
            <h2 class="mb-2 font-medium text-slate-900">{{ $table['title'] }}</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ $table['firstColumn'] }}</th>
                            <th class="px-4 py-2 text-right font-medium">Sewa baru</th>
                            <th class="px-4 py-2 text-right font-medium">Perpanjangan</th>
                            <th class="px-4 py-2 text-right font-medium">Biaya sewa</th>
                            <th class="px-4 py-2 text-right font-medium">Biaya listrik</th>
                            <th class="px-4 py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($table['rows'] as $row)
                            <tr class="{{ $row['new'] + $row['extension'] === 0 ? 'text-slate-400' : '' }}">
                                <td class="px-4 py-2">{{ $row['label'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['new'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $row['extension'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $rp($row['rent_fee']) }}</td>
                                <td class="px-4 py-2 text-right">{{ $rp($row['charging_fee']) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ $rp($row['total_fee']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

    <section class="mt-8">
        <h2 class="mb-2 font-medium text-slate-900">Rincian transaksi</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Tanggal</th>
                        <th class="px-4 py-2 font-medium">Garasi</th>
                        <th class="px-4 py-2 font-medium">Penyewa</th>
                        <th class="px-4 py-2 font-medium">Kendaraan</th>
                        <th class="px-4 py-2 font-medium">Jenis</th>
                        <th class="px-4 py-2 text-right font-medium">Total</th>
                        <th class="px-4 py-2 font-medium">Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($details as $invoice)
                        @php $rental = $invoice->rental; @endphp
                        <tr>
                            <td class="px-4 py-2">{{ $invoice->start_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 font-mono font-medium">{{ $rental->garage->code }}</td>
                            <td class="px-4 py-2">{{ $rental->renter->user->name }}</td>
                            <td class="px-4 py-2">
                                {{ $rental->vehicle->brand_model }}
                                @if ($rental->vehicle->plate_number)
                                    <span class="text-slate-500">({{ $rental->vehicle->plate_number }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                {{ $invoice->type === \App\Enums\InvoiceType::Initial ? 'Sewa baru' : 'Perpanjangan' }}
                                <span class="text-slate-500">({{ $invoice->duration_days }} hari)</span>
                            </td>
                            <td class="px-4 py-2 text-right font-medium">{{ $rp($invoice->total_fee) }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ asset('storage/'.$invoice->payment_proof_path) }}" target="_blank" rel="noopener"
                                    class="text-slate-600 underline hover:text-slate-900">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada transaksi pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $details->links() }}</div>
    </section>
@endsection
