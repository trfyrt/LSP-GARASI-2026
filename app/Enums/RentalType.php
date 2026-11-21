<?php

namespace App\Enums;

/**
 * Jenis durasi sewa.
 */
enum RentalType: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';
}
