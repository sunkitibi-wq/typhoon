<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionFee extends Model
{
    protected $fillable = ['transaction_id', 'fee_type', 'amount', 'currency', 'description'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
