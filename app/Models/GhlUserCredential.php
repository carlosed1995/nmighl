<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GhlUserCredential extends Model
{
    protected $table = 'ghl_user_credentials';

    protected $fillable = [
        'user_id',
        'private_integration_token',
        'default_location_id',
    ];

    protected function casts(): array
    {
        return [
            'private_integration_token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
