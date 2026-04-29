<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GhlLocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'ghl_id',
        'name',
        'company_id',
        'timezone',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'raw' => 'array',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(GhlClient::class);
    }
}
