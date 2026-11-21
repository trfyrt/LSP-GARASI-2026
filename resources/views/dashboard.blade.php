@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold text-slate-900">Halo, {{ $user->name }}</h1>

    @if ($user->isAdmin())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-medium text-slate-900">Panel Admin</h2>
            <p class="mt-1 text-sm text-slate-600">
                Menu verifikasi pendaftar, pembuatan garasi, perpanjangan sewa, dan laporan akan tersedia di tahap berikutnya.
            </p>
        </div>
    @else
        @php
            $status = $renter?->status;
            $banner = match ($status?->value) {
                'APPROVED' => ['border-green-200 bg-green-50 text-green-800', 'Akun Anda sudah diverifikasi. Anda dapat menyewa garasi.'],
                'REJECTED' => ['border-red-200 bg-red-50 text-red-800', 'Pendaftaran Anda ditolak oleh Admin, sehingga Anda tidak dapat menyewa garasi.'],
                default => ['border-amber-200 bg-amber-50 text-amber-800', 'Akun Anda menunggu verifikasi Admin. Anda belum dapat menyewa garasi.'],
            };
        @endphp

        <div class="mt-6 rounded-md border px-4 py-3 text-sm {{ $banner[0] }}">
            {{ $banner[1] }}
        </div>

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
