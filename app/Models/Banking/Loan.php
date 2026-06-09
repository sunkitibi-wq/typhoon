<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'user_id', 'account_id', 'loan_number', 'amount', 'interest_rate',
        'term_months', 'monthly_payment', 'total_payable', 'paid_amount',
        'purpose', 'collateral_description', 'collateral_value', 'status',
        'rejection_reason', 'applied_at', 'underwriting_at', 'approved_at',
        'rejected_at', 'disbursed_at', 'paid_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'monthly_payment' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'collateral_value' => 'decimal:2',
            'applied_at' => 'datetime',
            'underwriting_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'paid_at' => 'datetime',
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }
}
