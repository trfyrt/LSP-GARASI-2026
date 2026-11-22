@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Halo, {{ $user->name }}</h1>

    @if ($user->isAdmin())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-medium text-slate-900">Panel Admin</h2>
            <a href="{{ route('admin.renters.index') }}"
                class="mt-3 flex items-center justify-between rounded-md border border-slate-200 px-4 py-3 text-sm hover:bg-slate-50">
                <span class="font-medium text-slate-800">Verifikasi Pendaftar</span>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ $pendingCount }} menunggu</span>
            </a>
            <a href="{{ route('admin.garages.index') }}"
                class="mt-2 flex items-center justify-between rounded-md border border-slate-200 px-4 py-3 text-sm hover:bg-slate-50">
                <span class="font-medium text-slate-800">Garasi</span>
                <span class="text-xs text-slate-500">Buat garasi batch</span>
            </a>
            <a href="{{ route('admin.extensions.index') }}"
                class="mt-2 flex items-center justify-between rounded-md border border-slate-200 px-4 py-3 text-sm hover:bg-slate-50">
                <span class="font-medium text-slate-800">Perpanjang Sewa</span>
                <span class="text-xs text-slate-500">Garasi yang sedang digunakan</span>
            </a>
            <a href="{{ route('admin.reports.index') }}"
                class="mt-2 flex items-center justify-between rounded-md border border-slate-200 px-4 py-3 text-sm hover:bg-slate-50">
                <span class="font-medium text-slate-800">Laporan</span>
                <span class="text-xs text-slate-500">Rekap per bulan, hari, tipe, penyewa</span>
            </a>
        </div>
    @else
        @php
            $status = $renter?->status;
            $banner = match ($status?->value) {
                'APPROVED' => ['border-slate-200 bg-white text-slate-700', 'Akun Anda sudah diverifikasi. Anda dapat menyewa garasi.'],
                'REJECTED' => ['border-red-200 bg-red-50 text-red-800', 'Pendaftaran Anda ditolak oleh Admin, sehingga Anda tidak dapat menyewa garasi.'],
                default => ['border-amber-200 bg-amber-50 text-amber-800', 'Akun Anda menunggu verifikasi Admin. Anda belum dapat menyewa garasi.'],
            };
        @endphp

        <div class="mt-6 flex items-center gap-3 rounded-md border px-4 py-3 text-sm {{ $banner[0] }}">
            @if ($status?->value === 'APPROVED')
                <x-verified-badge />
            @endif
            <span>{{ $banner[1] }}</span>
        </div>

        @if ($renter?->canRent())
            <div class="mt-4 flex gap-2 text-sm">
                <a href="{{ route('rentals.create') }}" class="rounded-md bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-700">Sewa garasi baru</a>
                <a href="{{ route('rentals.index') }}" class="rounded-md border border-slate-300 px-4 py-2 font-medium text-slate-700 hover:bg-slate-100">Sewa saya</a>
            </div>
        @endif

        @if ($renter)
            <dl class="mt-6 grid max-w-md grid-cols-[8rem_1fr] gap-y-2 rounded-xl border border-slate-200 bg-white p-6 text-sm shadow-sm">
                <dt class="text-slate-500">Email</dt>
                <dd>{{ $user->email }}</dd>
                <dt class="text-slate-500">Nomor telepon</dt>
                <dd>{{ $renter->phone_number }}</dd>
                <dt class="text-slate-500">NIK</dt>
                <dd>{{ str_repeat('•', 12) }}{{ substr($renter->nik, -4) }}</dd>
            </dl>
        @endif
    @endif
@endsection
