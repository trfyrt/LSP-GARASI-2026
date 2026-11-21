@extends('layouts.app')

@section('title', 'Garasi')

@section('content')
    @php
        $input = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500';
        $selectedType = old('type');
    @endphp

    <h1 class="text-2xl font-semibold text-slate-900">Garasi</h1>
    <p class="mt-1 text-sm text-slate-500">Buat garasi secara berkelompok. Kode dibuat otomatis dan berurutan per tipe (S001, C001, E001).</p>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        @foreach ($types as $type)
            @php
                $counts = $summary[$type->value] ?? [];
                $total = array_sum($counts);
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-sm text-slate-500">{{ $type->label() }} ({{ $type->prefix() }})</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $total }}</div>
                <div class="text-xs text-slate-500">{{ $counts[$availableStatus] ?? 0 }} tersedia &middot; sisa slot kode {{ 999 - $total }}</div>
            </div>
        @endforeach
    </div>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 font-medium text-slate-900">Buat garasi batch</h2>

        <form method="POST" action="{{ route('admin.garages.store') }}" class="grid gap-4 sm:grid-cols-2" novalidate>
            @csrf

            <div>
                <label for="type" class="mb-1 block text-sm font-medium">Tipe garasi</label>
                <select id="type" name="type" required class="{{ $input }}">
                    <option value="">Pilih tipe</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected($selectedType === $type->value)>
                            {{ $type->label() }} ({{ $type->prefix() }})
                        </option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="count" class="mb-1 block text-sm font-medium">Jumlah garasi (maks. 999 per tipe)</label>
                <input id="count" name="count" type="number" min="1" max="999" value="{{ old('count') }}" required
                    class="{{ $input }}">
                @error('count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="daily_price" class="mb-1 block text-sm font-medium">Harga sewa harian (Rp)</label>
                <input id="daily_price" name="daily_price" type="number" min="1" value="{{ old('daily_price') }}" required
                    class="{{ $input }}">
                @error('daily_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="charging-field">
                <label for="charging_price" class="mb-1 block text-sm font-medium">Harga isi listrik per kWh (Rp, khusus Mobil Listrik)</label>
                <input id="charging_price" name="charging_price" type="number" min="1" value="{{ old('charging_price') }}"
                    class="{{ $input }}">
                @error('charging_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Buat garasi
                </button>
            </div>
        </form>
    </section>

    <section class="mt-8">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-medium text-slate-900">Daftar garasi</h2>
            <div class="flex gap-1 text-sm">
                <a href="{{ route('admin.garages.index') }}"
                    class="rounded-md px-3 py-1 {{ $filter === null ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}">Semua</a>
                @foreach ($types as $type)
                    <a href="{{ route('admin.garages.index', ['type' => $type->value]) }}"
                        class="rounded-md px-3 py-1 {{ $filter === $type ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:bg-slate-100' }}">{{ $type->prefix() }}</a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Kode</th>
                        <th class="px-4 py-2 font-medium">Tipe</th>
                        <th class="px-4 py-2 text-right font-medium">Harga harian</th>
                        <th class="px-4 py-2 text-right font-medium">Harga listrik/kWh</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($garages as $garage)
                        <tr>
                            <td class="px-4 py-2 font-mono font-medium">{{ $garage->code }}</td>
                            <td class="px-4 py-2">{{ $garage->type->label() }}</td>
                            <td class="px-4 py-2 text-right">Rp {{ number_format($garage->daily_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">
                                {{ $garage->charging_price === null ? '-' : 'Rp '.number_format($garage->charging_price, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $garage->isAvailable() ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $garage->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada garasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $garages->links() }}</div>
    </section>

    <script>
        // Harga isi listrik hanya relevan untuk garasi mobil listrik (EV).
        const typeSelect = document.getElementById('type');
        const chargingField = document.getElementById('charging-field');
        const syncChargingField = () => chargingField.classList.toggle('hidden', typeSelect.value !== 'EV');
        typeSelect.addEventListener('change', syncChargingField);
        syncChargingField();
    </script>
@endsection
