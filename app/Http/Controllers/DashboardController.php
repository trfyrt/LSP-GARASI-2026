<?php

namespace App\Http\Controllers;

use App\Enums\RenterStatus;
use App\Models\Renter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Halaman awal setelah login. Isinya menyesuaikan peran pengguna.
 */
class DashboardController extends Controller
{
    /** Tampilkan dashboard sesuai peran (Admin atau Penyewa) dan status verifikasi. */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'renter' => $user->renter,
            'pendingCount' => $user->isAdmin() ? Renter::where('status', RenterStatus::Pending)->count() : 0,
        ]);
    }
}
