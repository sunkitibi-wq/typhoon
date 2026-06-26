<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'account_type_id', 'account_number', 'iban', 'swift_bic',
        'currency', 'balance', 'available_balance', 'ledger_balance',
        'status', 'label', 'is_default', 'is_joint', 'closed_at',
        'solaris_account_id',
    ];

    protected $appends = ['number'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_joint' => 'boolean',
            'closed_at' => 'datetime',
            'balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'ledger_balance' => 'decimal:2',
        ];
    }

    public function getNumberAttribute(): string
    {
        return $this->account_number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function debitTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'debit_account_id');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'credit_account_id');
    }
}
