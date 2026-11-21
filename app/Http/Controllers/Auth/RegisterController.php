<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\RenterRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Registrasi penyewa. Akun baru berstatus Pending sampai diverifikasi Admin.
 */
class RegisterController extends Controller
{
    /** Tampilkan form registrasi. */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Simpan pendaftar baru, lalu arahkan ke halaman login. */
    public function store(RegisterRequest $request, RenterRegistrationService $service): RedirectResponse
    {
        $service->register($request->validated());

        return redirect()->route('login')->with(
            'status',
            'Registrasi berhasil. Akun Anda menunggu verifikasi Admin sebelum dapat menyewa garasi.',
        );
    }
}
