<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTerminal extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'serial_number', 'oracle_terminal_id', 'label', 'model',
        'crypto_processor_enabled', 'default_crypto_currency', 'settlement_mode',
        'oracle_api_url', 'oracle_api_key', 'status', 'pairing_code', 'paired_at', 'last_active_at',
    ];

    protected $hidden = [
        'oracle_api_key',
        'pairing_code',
    ];

    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_active_at' => 'datetime',
            'crypto_processor_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function posTransactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }
}
