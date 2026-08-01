<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanctionsScreening extends Model
{
    protected $fillable = [
        'user_id', 'list_type', 'matched_term', 'match_score',
        'status', 'screening_result', 'metadata', 'screened_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'screening_result' => 'array',
            'metadata' => 'array',
            'screened_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
