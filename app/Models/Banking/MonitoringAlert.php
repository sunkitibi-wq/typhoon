<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlert extends Model
{
    protected $table = 'monitoring_alerts';

    protected $fillable = [
        'monitoring_rule_id', 'user_id', 'transaction_id', 'alert_type',
        'severity', 'status', 'description', 'details', 'amount',
        'resolved_at', 'resolved_by', 'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'resolved_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function monitoringRule(): BelongsTo
    {
        return $this->belongsTo(MonitoringRule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
