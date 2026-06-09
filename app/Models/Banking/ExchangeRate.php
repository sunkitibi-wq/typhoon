<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency', 'quote_currency', 'bid', 'ask', 'mid_rate',
        'change_24h', 'volume_24h', 'high_24h', 'low_24h', 'last_refreshed_at',
    ];

    protected $casts = [
        'bid' => 'float',
        'ask' => 'float',
        'mid_rate' => 'float',
        'change_24h' => 'float',
        'last_refreshed_at' => 'datetime',
    ];
}
