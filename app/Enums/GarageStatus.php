<?php

namespace App\Enums;

/**
 * Status ketersediaan garasi.
 */
enum GarageStatus: string
{
    case Available = 'available';
    case Used = 'used';
}
