<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkPaymentItem extends Model
{
    protected $fillable = [
        'bulk_payment_id', 'transaction_id', 'beneficiary_name',
        'beneficiary_iban', 'beneficiary_bic', 'amount',
        'reference', 'status', 'failure_reason',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function bulkPayment(): BelongsTo
    {
        return $this->belongsTo(BulkPayment::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
