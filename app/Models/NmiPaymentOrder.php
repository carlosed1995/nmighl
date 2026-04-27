<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NmiPaymentOrder extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_ERROR = 'error';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'user_id',
        'ghl_client_id',
        'ghl_contact_id',
        'ghl_location_id',
        'ghl_order_id',
        'amount',
        'currency',
        'description',
        'source',
        'status',
        'nmi_transaction_id',
        'nmi_order_id',
        'nmi_invoice_id',
        'response_message',
        'synced_to_ghl_at',
        'ghl_sync_error',
        'gateway_payload',
        'webhook_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'synced_to_ghl_at' => 'datetime',
            'gateway_payload' => 'array',
            'webhook_payload' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(GhlClient::class, 'ghl_client_id');
    }
}
