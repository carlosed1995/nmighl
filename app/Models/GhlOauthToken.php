<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GhlOauthToken extends Model
{
    protected $fillable = [
        'provider',
        'location_id',
        'company_id',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'expires_at',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function scopeLatestValid(Builder $query): Builder
    {
        return $query
            ->where('provider', 'ghl')
            ->orderByDesc('id');
    }

    public function isExpiringSoon(): bool
    {
        if (! $this->expires_at instanceof Carbon) {
            return false;
        }

        return $this->expires_at->lessThanOrEqualTo(now()->addMinutes(5));
    }
}
