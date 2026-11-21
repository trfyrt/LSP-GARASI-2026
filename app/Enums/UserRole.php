<?php

namespace App\Enums;

/**
 * Peran pengguna dalam sistem.
 */
enum UserRole: string
{
    case Admin = 'ADMIN';
    case Renter = 'RENTER';
}
