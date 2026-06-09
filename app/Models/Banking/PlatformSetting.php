<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $fillable = ['key', 'value', 'group', 'type', 'description', 'is_encrypted'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }
}
