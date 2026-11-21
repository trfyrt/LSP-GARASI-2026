<?php

namespace App\Models;

use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tagihan satu periode sewa: tanggal, rincian biaya, dan bukti pembayaran.
 */
#[Fillable([
    'id_rental', 'type', 'start_date', 'end_date', 'duration_days',
    'rent_fee', 'charging_fee', 'total_fee', 'payment_proof_path',
])]
class Invoice extends Model
{
    protected $table = 'invoice';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'id_rental');
    }
}
