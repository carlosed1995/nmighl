<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GhlClient extends Model
{
    use HasFactory;
    protected $fillable = [
        'ghl_location_id',
        'ghl_contact_id',
        'name',
        'email',
        'phone',
        'tags',
        'last_activity_at',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'last_activity_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(GhlLocation::class, 'ghl_location_id');
    }
}
