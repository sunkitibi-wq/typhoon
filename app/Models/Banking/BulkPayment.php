<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkPayment extends Model
{
    protected $fillable = [
        'batch_reference', 'business_profile_id', 'debit_account_id',
        'total_transactions', 'total_amount', 'currency', 'type',
        'status', 'metadata', 'processed_at', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'processed_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function businessProfile(): BelongsTo
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkPaymentItem::class);
    }
}
