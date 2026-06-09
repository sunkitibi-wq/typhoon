<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    protected $fillable = [
        'name', 'fee_type', 'calculation_method', 'fee_value',
        'min_fee', 'max_fee', 'currency', 'tiers',
        'applicable_channels', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tiers' => 'array',
            'applicable_channels' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
