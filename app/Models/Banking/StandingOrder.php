<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandingOrder extends Model
{
    protected $fillable = [
        'user_id', 'debit_account_id', 'beneficiary_id',
        'beneficiary_iban', 'beneficiary_bic', 'beneficiary_name',
        'amount', 'currency', 'frequency', 'schedule', 'reference',
        'notes', 'starts_at', 'ends_at', 'last_executed_at', 'next_execution_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'last_executed_at' => 'datetime',
            'next_execution_at' => 'datetime',
            'amount' => 'decimal:2',
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

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
