<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') - {{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        @auth
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                    <nav class="flex items-center gap-5 text-sm">
                        <a href="{{ route('dashboard') }}" class="font-semibold text-slate-900">
                            {{ config('app.name') }}
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.renters.index') }}"
                                class="{{ request()->routeIs('admin.renters.*') ? 'font-medium text-slate-900' : 'text-slate-600 hover:text-slate-900' }}">
                                Verifikasi Pendaftar
                            </a>
                        @endif
                    </nav>

                    <div class="flex items-center gap-3 text-sm">
                        <span class="text-slate-600">{{ auth()->user()->name }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                            {{ auth()->user()->isAdmin() ? 'Admin' : 'Penyewa' }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-slate-300 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-100">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </header>
        @endauth

        <main class="mx-auto max-w-5xl px-4 py-8">
            @yield('content')
        </main>
    </body>
</html>
