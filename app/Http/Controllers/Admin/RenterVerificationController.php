<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RenterStatus;
use App\Http\Controllers\Controller;
use App\Models\Renter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Admin memverifikasi pendaftar baru: menerima atau menolak.
 */
class RenterVerificationController extends Controller
{
    /** Daftar pendaftar yang menunggu verifikasi beserta riwayat keputusan. */
    public function index(): View
    {
        return view('admin.renters.index', [
            'pending' => Renter::with('user')
                ->where('status', RenterStatus::Pending)
                ->orderBy('id')
                ->get(),
            'reviewed' => Renter::with('user')
                ->where('status', '!=', RenterStatus::Pending)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    /** Terima pendaftar. */
    public function approve(Renter $renter): RedirectResponse
    {
        return $this->decide($renter, true);
    }

    /** Tolak pendaftar. */
    public function reject(Renter $renter): RedirectResponse
    {
        return $this->decide($renter, false);
    }

    /** Terapkan keputusan hanya pada pendaftar yang masih Pending. */
    private function decide(Renter $renter, bool $accept): RedirectResponse
    {
        if ($renter->status !== RenterStatus::Pending) {
            return back()->with('error', "Pendaftar {$renter->user->name} sudah diverifikasi sebelumnya.");
        }

        $renter->verify($accept);

        return back()->with(
            'status',
            "Pendaftar {$renter->user->name} ".($accept ? 'diterima.' : 'ditolak.'),
        );
    }
}
