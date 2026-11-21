@extends('layouts.app')

@section('title', 'Registrasi')

@section('content')
    @php
        $input = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500';
    @endphp

    <div class="mx-auto mt-4 w-full max-w-md">
        <h1 class="mb-1 text-center text-2xl font-semibold text-slate-900">Registrasi Penyewa</h1>
        <p class="mb-6 text-center text-sm text-slate-500">Akun baru perlu diverifikasi Admin sebelum dapat menyewa garasi.</p>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ url('/register') }}" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">Nama lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        autocomplete="name" class="{{ $input }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nik" class="mb-1 block text-sm font-medium">NIK (No. KTP)</label>
                    <input id="nik" name="nik" type="text" value="{{ old('nik') }}" required inputmode="numeric"
                        maxlength="16" placeholder="16 digit" class="{{ $input }}">
                    @error('nik')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone_number" class="mb-1 block text-sm font-medium">Nomor telepon</label>
                    <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number') }}" required
                        autocomplete="tel" placeholder="08xxxxxxxxxx" class="{{ $input }}">
                    @error('phone_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email (dipakai untuk login)</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        autocomplete="username" class="{{ $input }}">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="{{ $input }}">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        autocomplete="new-password" class="{{ $input }}">
                </div>

                <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Daftar
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-medium text-slate-900 underline">Masuk</a>
        </p>
    </div>
@endsection
