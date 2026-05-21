<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoDeposit extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'crypto_currency_id', 'crypto_wallet_id',
        'tx_hash', 'amount', 'fee', 'net_amount', 'from_address',
        'status', 'confirmations', 'blockchain_meta', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'blockchain_meta' => 'array',
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
}
