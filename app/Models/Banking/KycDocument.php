<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    protected $fillable = [
        'kyc_verification_id', 'document_type', 'file_path',
        'mime_type', 'file_size', 'status', 'rejection_reason',
    ];

    public function kycVerification(): BelongsTo
    {
        return $this->belongsTo(KycVerification::class);
    }
}
