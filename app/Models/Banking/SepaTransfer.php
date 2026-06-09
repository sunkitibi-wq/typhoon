<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SepaTransfer extends Model
{
    protected $fillable = [
        'transaction_id', 'sepa_type', 'creditor_name', 'creditor_iban',
        'creditor_bic', 'remittance_info', 'end_to_end_id', 'purpose_code',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
