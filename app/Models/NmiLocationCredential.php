<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NmiLocationCredential extends Model
{
    protected $fillable = [
        'ghl_location_id',
        'api_security_key',
        'webhook_signing_key',
        'webhook_secret',
        'subscription_id',
        'subscribed_events',
        'subscription_last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'api_security_key' => 'encrypted',
            'webhook_signing_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'subscribed_events' => 'array',
            'subscription_last_synced_at' => 'datetime',
        ];
    }
}
