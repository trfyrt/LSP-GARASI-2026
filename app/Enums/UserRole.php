<?php

namespace App\Enums;

/**
 * Peran pengguna dalam sistem.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Renter = 'renter';
}
