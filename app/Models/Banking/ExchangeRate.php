<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency', 'quote_currency', 'bid', 'ask', 'mid_rate',
        'change_24h', 'volume_24h', 'high_24h', 'low_24h', 'last_refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_refreshed_at' => 'datetime',
        ];
    }
}
