<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'registration_number', 'tax_id',
        'vat_number', 'registered_address', 'business_type', 'industry',
        'website', 'contact_email', 'contact_phone', 'founded_year',
        'employee_count', 'annual_revenue', 'documents', 'status', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'verified_at' => 'datetime',
            'annual_revenue' => 'decimal:2',
            'founded_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function corporateUsers(): HasMany
    {
        return $this->hasMany(CorporateUser::class);
    }

    public function bulkPayments(): HasMany
    {
        return $this->hasMany(BulkPayment::class);
    }
}
