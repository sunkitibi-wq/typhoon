<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycVerification extends Model
{
    protected $fillable = [
        'user_id', 'kyc_level', 'status', 'id_type', 'id_number',
        'id_expiry_date', 'country', 'nationality', 'date_of_birth',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code',
        'source_of_funds', 'occupation', 'employer', 'annual_income_range',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'id_expiry_date' => 'date',
            'verified_at' => 'datetime',
            'annual_income_range' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }
}
