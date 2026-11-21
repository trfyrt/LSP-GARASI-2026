<?php

namespace App\Enums;

/**
 * Status ketersediaan garasi.
 */
enum GarageStatus: string
{
    case Available = 'AVAILABLE';
    case Used = 'USED';

    /** Nama tampilan status. */
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Used => 'Digunakan',
        };
    }
}
