<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwiftTransfer extends Model
{
    protected $fillable = [
        'transaction_id', 'beneficiary_name', 'beneficiary_account',
        'beneficiary_bic', 'beneficiary_bank_name', 'beneficiary_bank_address',
        'beneficiary_address', 'intermediary_bic', 'remittance_info',
        'charge_bearer', 'purpose_of_payment', 'sender_reference',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
