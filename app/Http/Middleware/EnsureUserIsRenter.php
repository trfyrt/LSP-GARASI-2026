<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses halaman hanya untuk pengguna yang memiliki profil penyewa.
 */
class EnsureUserIsRenter
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->renter !== null, 403, 'Halaman ini hanya untuk Penyewa.');

        return $next($request);
    }
}
