<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses halaman hanya untuk pengguna berperan Admin.
 */
class EnsureUserIsAdmin
{
    /** Lanjutkan permintaan hanya jika pengguna berperan Admin; selain itu respons 403. */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Halaman ini hanya untuk Admin.');

        return $next($request);
    }
}
