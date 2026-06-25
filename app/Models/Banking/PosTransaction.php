<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransaction extends Model
{
    protected $fillable = [
        'transaction_id', 'pos_terminal_id', 'card_brand', 'card_last4',
        'payment_method', 'payment_type', 'crypto_currency', 'crypto_amount',
        'crypto_address', 'tx_hash', 'terminal_reference', 'amount', 'currency',
        'status', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'crypto_amount' => 'decimal:8',
        ];
    }

    public function posTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
