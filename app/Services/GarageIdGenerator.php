<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Models\Garage;
use InvalidArgumentException;

/**
 * Membuat kode garasi otomatis dan berurutan per tipe: S001, C001, E001, dst.
 */
class GarageIdGenerator
{
    /** Nomor urut terbesar per tipe (3 digit). */
    public const MAX_PER_TYPE = 999;

    /**
     * Hasilkan `$count` kode berikutnya setelah nomor terakhir yang sudah dipakai.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException jika jumlah < 1 atau melewati batas 999 per tipe
     */
    public function generate(GarageType $type, int $lastNumber, int $count): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Jumlah garasi minimal 1.');
        }

        $remaining = max(0, self::MAX_PER_TYPE - $lastNumber);
        if ($count > $remaining) {
            throw new InvalidArgumentException(
                "Kode {$type->label()} maksimal ".self::MAX_PER_TYPE.". Sisa slot: {$remaining}."
            );
        }

        $codes = [];
        for ($number = $lastNumber + 1; $number <= $lastNumber + $count; $number++) {
            $codes[] = $type->prefix().str_pad((string) $number, 3, '0', STR_PAD_LEFT);
        }

        return $codes;
    }

    /** Ambil nomor urut dari sebuah kode (`S042` menjadi 42); null berarti belum ada garasi. */
    public function numberFromCode(?string $code): int
    {
        return $code === null ? 0 : (int) substr($code, 1);
    }

    /** Nomor urut terakhir yang sudah dipakai untuk tipe ini (0 jika belum ada). */
    public function lastNumber(GarageType $type): int
    {
        return $this->numberFromCode(
            Garage::where('type', $type)->orderByDesc('code')->value('code')
        );
    }

    /** Sisa slot kode yang masih bisa dibuat untuk tipe ini. */
    public function remaining(GarageType $type): int
    {
        return max(0, self::MAX_PER_TYPE - $this->lastNumber($type));
    }
}
