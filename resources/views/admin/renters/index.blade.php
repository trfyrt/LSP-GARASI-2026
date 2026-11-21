@extends('layouts.app')

@section('title', 'Verifikasi Pendaftar')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Verifikasi Pendaftar</h1>
    <p class="mt-1 text-sm text-slate-500">Periksa data NIK calon penyewa, lalu terima atau tolak pendaftarannya.</p>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <section class="mt-6">
        <h2 class="mb-2 font-medium text-slate-900">
            Menunggu verifikasi
            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ $pending->count() }}</span>
        </h2>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">NIK</th>
                        <th class="px-4 py-2 font-medium">Telepon</th>
                        <th class="px-4 py-2 font-medium">Email</th>
                        <th class="px-4 py-2 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pending as $renter)
                        <tr>
                            <td class="px-4 py-2">{{ $renter->user->name }}</td>
                            <td class="px-4 py-2 font-mono">{{ $renter->nik }}</td>
                            <td class="px-4 py-2">{{ $renter->phone_number }}</td>
                            <td class="px-4 py-2">{{ $renter->user->email }}</td>
                            <td class="px-4 py-2">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.renters.approve', $renter) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                            Terima
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.renters.reject', $renter) }}"
                                        onsubmit="return confirm('Tolak pendaftar {{ e($renter->user->name) }}?')">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                                            Tolak
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Tidak ada pendaftar yang menunggu verifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8">
        <h2 class="mb-2 font-medium text-slate-900">Riwayat verifikasi</h2>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-medium">Nama</th>
                        <th class="px-4 py-2 font-medium">NIK</th>
                        <th class="px-4 py-2 font-medium">Email</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reviewed as $renter)
                        <tr>
                            <td class="px-4 py-2">{{ $renter->user->name }}</td>
                            <td class="px-4 py-2 font-mono">{{ $renter->nik }}</td>
                            <td class="px-4 py-2">{{ $renter->user->email }}</td>
                            <td class="px-4 py-2">
                                @if ($renter->status->value === 'APPROVED')
                                    <x-verified-badge />
                                @else
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Ditolak</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada riwayat verifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
