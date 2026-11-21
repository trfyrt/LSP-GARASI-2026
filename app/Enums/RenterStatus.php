<?php

namespace App\Enums;

/**
 * Status verifikasi penyewa oleh Admin. Hanya `Approved` yang boleh menyewa.
 */
enum RenterStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
}
