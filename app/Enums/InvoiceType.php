<?php

namespace App\Enums;

/**
 * Jenis invoice: tagihan sewa baru atau tagihan perpanjangan.
 */
enum InvoiceType: string
{
    case Initial = 'INITIAL';
    case Extension = 'EXTENSION';
}
