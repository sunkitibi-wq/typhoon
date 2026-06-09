<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTerminal extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'serial_number', 'label', 'model',
        'status', 'pairing_code', 'paired_at', 'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'last_active_at' => 'datetime',
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
