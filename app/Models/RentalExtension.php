<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan perpanjangan sewa yang diproses Admin.
 */
#[Fillable(['rental_id', 'admin_id', 'old_end_date', 'new_end_date', 'extra_days', 'extra_fee', 'payment_proof_path'])]
class RentalExtension extends Model
{
    protected function casts(): array
    {
        return [
            'old_end_date' => 'date',
            'new_end_date' => 'date',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /** Admin yang memproses perpanjangan. */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
