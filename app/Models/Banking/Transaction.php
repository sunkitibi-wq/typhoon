<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'reference', 'type', 'status', 'debit_account_id', 'credit_account_id',
        'user_id', 'amount', 'fee', 'net_amount', 'currency', 'description',
        'category', 'metadata', 'failure_reason', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'completed_at' => 'datetime',
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(TransactionFee::class);
    }

    public function sepaTransfer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SepaTransfer::class);
    }

    public function swiftTransfer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SwiftTransfer::class);
    }

    public function posTransaction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PosTransaction::class);
    }
}
