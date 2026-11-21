@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="mx-auto mt-8 w-full max-w-md">
        <h1 class="mb-1 text-center text-2xl font-semibold text-slate-900">{{ config('app.name') }}</h1>
        <p class="mb-6 text-center text-sm text-slate-500">Masuk untuk melanjutkan</p>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ url('/login') }}" class="space-y-4" novalidate>
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        autocomplete="username"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Masuk
                </button>
            </form>
        </div>

        <p class="mt-4 text-center text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-medium text-slate-900 underline">Daftar sebagai penyewa</a>
        </p>
    </div>
@endsection
