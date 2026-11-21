<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis penyewaan/perpanjangan. Pesan aman ditampilkan ke pengguna.
 */
class RentalException extends RuntimeException {}
