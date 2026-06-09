<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;

class MonitoringRule extends Model
{
    protected $table = 'monitoring_rules';

    protected $fillable = [
        'name', 'category', 'rule_type', 'conditions',
        'severity', 'is_active', 'description', 'action',
    ];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'is_active' => 'boolean'];
    }
}
