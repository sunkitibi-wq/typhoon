<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspiciousActivity extends Model
{
    protected $table = 'suspicious_activities';

    protected $fillable = [
        'user_id', 'transaction_id', 'risk_level', 'category',
        'description', 'evidence', 'status', 'reported_at',
        'reported_by', 'resolved_at', 'resolved_by', 'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
