<?php

namespace App\Enums;

/**
 * Jenis durasi sewa.
 */
enum RentalType: string
{
    case Daily = 'DAILY';
    case Monthly = 'MONTHLY';
}
