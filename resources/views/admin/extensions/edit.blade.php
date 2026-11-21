@extends('layouts.app')

@section('title', 'Perpanjang Sewa')

@section('content')
    @php
        $input = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500';
        $endDate = $rental->endDate();
    @endphp

    <a href="{{ route('admin.extensions.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali</a>
    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Perpanjang Sewa Garasi {{ $rental->garage->code }}</h1>

    <dl class="mt-4 grid max-w-md grid-cols-[10rem_1fr] gap-y-1 rounded-xl border border-slate-200 bg-white p-6 text-sm shadow-sm">
        <dt class="text-slate-500">Penyewa</dt>
        <dd>{{ $rental->renter->user->name }}</dd>
        <dt class="text-slate-500">Kendaraan</dt>
        <dd>{{ $rental->vehicle->brand_model }} {{ $rental->vehicle->plate_number ? '('.$rental->vehicle->plate_number.')' : '' }}</dd>
        <dt class="text-slate-500">Harga harian</dt>
        <dd>Rp {{ number_format($rental->garage->daily_price, 0, ',', '.') }}</dd>
        <dt class="text-slate-500">Selesai saat ini</dt>
        <dd>{{ $endDate->translatedFormat('d F Y') }}</dd>
    </dl>

    <form method="POST" action="{{ route('admin.extensions.update', $rental) }}" enctype="multipart/form-data" novalidate
        class="mt-6 max-w-md space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="new_end_date" class="mb-1 block text-sm font-medium">Tanggal selesai baru</label>
            <input id="new_end_date" name="new_end_date" type="date" required
                min="{{ $endDate->addDay()->toDateString() }}" value="{{ old('new_end_date') }}" class="{{ $input }}">
            @error('new_end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <dl class="grid grid-cols-[10rem_1fr] gap-y-1 rounded-md bg-slate-50 p-4 text-sm">
            <dt class="text-slate-500">Tambahan hari</dt>
            <dd id="extra-days">-</dd>
            <dt class="font-medium text-slate-900">Sisa biaya tambahan</dt>
            <dd id="extra-fee" class="font-semibold text-slate-900">-</dd>
        </dl>

        <div>
            <label for="payment_proof" class="mb-1 block text-sm font-medium">Bukti pembayaran tambahan</label>
            <input id="payment_proof" name="payment_proof" type="file" accept="image/png,image/jpeg" required
                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-1 text-xs text-slate-500">Gambar JPG/PNG, maksimal 2 MB.</p>
            @error('payment_proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
            Simpan perpanjangan
        </button>
    </form>

    <script>
        (() => {
            const daily = {{ $rental->garage->daily_price }};
            const currentEnd = new Date('{{ $endDate->toDateString() }}T00:00:00');
            const input = document.getElementById('new_end_date');

            function preview() {
                const value = input.value ? new Date(input.value + 'T00:00:00') : null;
                const extra = value ? Math.round((value - currentEnd) / 86400000) : 0;
                document.getElementById('extra-days').textContent = extra > 0 ? extra + ' hari' : '-';
                document.getElementById('extra-fee').textContent = extra > 0
                    ? 'Rp ' + (extra * daily).toLocaleString('id-ID') : '-';
            }

            input.addEventListener('input', preview);
            preview();
        })();
    </script>
@endsection
