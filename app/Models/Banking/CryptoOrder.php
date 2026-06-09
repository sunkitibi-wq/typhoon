<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoOrder extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'order_type', 'side',
        'base_currency', 'quote_currency', 'amount', 'filled_amount',
        'price', 'stop_price', 'fee', 'fee_rate', 'total',
        'status', 'time_in_force', 'failure_reason', 'expires_at', 'filled_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'filled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
