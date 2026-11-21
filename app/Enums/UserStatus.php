<?php

namespace App\Enums;

/**
 * Status verifikasi akun penyewa. Hanya `Approved` yang boleh menyewa.
 */
enum UserStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
