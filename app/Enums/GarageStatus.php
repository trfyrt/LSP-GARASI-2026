<?php

namespace App\Enums;

/**
 * Status ketersediaan garasi.
 */
enum GarageStatus: string
{
    case Available = 'AVAILABLE';
    case Used = 'USED';
}
