@extends('layouts.app')

@section('title', 'Sewa Saya')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">Sewa Saya</h1>
        @if (auth()->user()->renter?->canRent())
            <a href="{{ route('rentals.create') }}" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Sewa baru</a>
        @endif
    </div>

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
                    <th class="px-4 py-2 font-medium">Kendaraan</th>
                    <th class="px-4 py-2 font-medium">Tipe sewa</th>
                    <th class="px-4 py-2 font-medium">Periode</th>
                    <th class="px-4 py-2 text-right font-medium">Total biaya</th>
                    <th class="px-4 py-2 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rentals as $rental)
                    <tr>
                        <td class="px-4 py-2 font-mono font-medium">{{ $rental->garage->code }}</td>
                        <td class="px-4 py-2">
                            {{ $rental->vehicle->brand_model }}
                            @if ($rental->vehicle->plate_number)
                                <span class="text-slate-500">({{ $rental->vehicle->plate_number }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $rental->rental_type === \App\Enums\RentalType::Monthly ? 'Bulanan' : 'Harian' }}</td>
                        <td class="px-4 py-2">{{ $rental->startDate()->format('d/m/Y') }} - {{ $rental->endDate()->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($rental->totalFee(), 0, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $rental->endDate()->gte($today) ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700' }}">
                                {{ $rental->endDate()->gte($today) ? 'Berjalan' : 'Selesai' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada sewa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
