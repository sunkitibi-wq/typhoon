<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoWallet extends Model
{
    protected $fillable = [
        'user_id', 'crypto_currency_id', 'address', 'label',
        'balance', 'locked_balance', 'status', 'private_key',
    ];

    protected $hidden = [
        'private_key',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:8',
            'locked_balance' => 'decimal:8',
            'private_key' => 'encrypted',
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
}
