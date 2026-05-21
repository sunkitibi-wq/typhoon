<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    protected $table = 'account_beneficiaries';

    protected $fillable = [
        'user_id', 'name', 'iban', 'bic', 'account_number',
        'bank_name', 'bank_country', 'email', 'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
