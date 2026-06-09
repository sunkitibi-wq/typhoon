<?php

namespace App\Models\Banking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiClient extends Model
{
    protected $fillable = [
        'user_id', 'name', 'client_id', 'client_secret',
        'scopes', 'allowed_ips', 'status', 'last_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'allowed_ips' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected $hidden = ['client_secret'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
