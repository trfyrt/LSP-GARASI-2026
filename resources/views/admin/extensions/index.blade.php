@extends('layouts.app')

@section('title', 'Perpanjang Sewa')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Perpanjang Sewa</h1>
    <p class="mt-1 text-sm text-slate-500">Garasi yang sedang disewa hari ini. Pilih garasi untuk mengubah tanggal selesai sewa.</p>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-4 py-2 font-medium">Garasi</th>
                    <th class="px-4 py-2 font-medium">Penyewa</th>
                    <th class="px-4 py-2 font-medium">Kendaraan</th>
                    <th class="px-4 py-2 font-medium">Selesai sewa</th>
                    <th class="px-4 py-2 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rentals as $rental)
                    <tr>
                        <td class="px-4 py-2 font-mono font-medium">{{ $rental->garage->code }}</td>
                        <td class="px-4 py-2">{{ $rental->renter->user->name }}</td>
                        <td class="px-4 py-2">
                            {{ $rental->vehicle->brand_model }}
                            @if ($rental->vehicle->plate_number)
                                <span class="text-slate-500">({{ $rental->vehicle->plate_number }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $rental->endDate()->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('admin.extensions.edit', $rental) }}"
                                class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">Perpanjang</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada garasi yang sedang digunakan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
