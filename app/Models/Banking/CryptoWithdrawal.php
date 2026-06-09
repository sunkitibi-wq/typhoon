<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoWithdrawal extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'crypto_currency_id', 'crypto_wallet_id',
        'tx_hash', 'to_address', 'amount', 'fee', 'net_amount',
        'status', 'rejection_reason', 'blockchain_meta',
        'approved_at', 'approved_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'blockchain_meta' => 'array',
            'approved_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cryptoCurrency(): BelongsTo
    {
        return $this->belongsTo(CryptoCurrency::class);
    }

    public function cryptoWallet(): BelongsTo
    {
        return $this->belongsTo(CryptoWallet::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
