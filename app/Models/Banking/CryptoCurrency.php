<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoCurrency extends Model
{
    protected $fillable = [
        'code', 'coingecko_id', 'name', 'network', 'contract_address', 'decimals',
        'minimum_withdrawal', 'withdrawal_fee', 'minimum_deposit',
        'deposit_fee', 'status', 'icon_url', 'is_quote_currency',
    ];

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
            'is_quote_currency' => 'boolean',
        ];
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(CryptoWallet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CryptoOrder::class, 'base_currency', 'code');
    }
}
