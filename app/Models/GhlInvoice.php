<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GhlInvoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'ghl_client_id',
        'ghl_location_id',
        'ghl_invoice_id',
        'invoice_number',
        'issued_date',
        'due_date',
        'amount',
        'status',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'issued_date' => 'date',
            'due_date'    => 'date',
            'raw'         => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(GhlClient::class, 'ghl_client_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(GhlLocation::class, 'ghl_location_id');
    }
}
