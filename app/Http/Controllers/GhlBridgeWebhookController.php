<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\NmiPaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GhlBridgeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->assertWebhookSecret($request);

        $payload = $request->all();

        $orderId = (string) (
            data_get($payload, 'orderId')
            ?? data_get($payload, 'order.id')
            ?? data_get($payload, 'invoiceId')
            ?? data_get($payload, 'invoice.id')
            ?? ''
        );
        $contactId = (string) (
            data_get($payload, 'contactId')
            ?? data_get($payload, 'contact.id')
            ?? ''
        );
        $locationId = (string) (
            data_get($payload, 'locationId')
            ?? data_get($payload, 'location.id')
            ?? ''
        );

        $amount = (float) (
            data_get($payload, 'amount')
            ?? data_get($payload, 'order.amount')
            ?? data_get($payload, 'invoice.amount')
            ?? 0
        );
        $currency = strtoupper((string) (
            data_get($payload, 'currency')
            ?? data_get($payload, 'order.currency')
            ?? 'USD'
        ));
        $description = (string) (
            data_get($payload, 'description')
            ?? data_get($payload, 'order.description')
            ?? 'GHL order webhook'
        );

        if ($orderId === '' || $amount <= 0) {
            return response('ignored', 202);
        }

        $client = null;
        if ($contactId !== '') {
            $client = GhlClient::query()->where('ghl_contact_id', $contactId)->first();
        }

        NmiPaymentOrder::query()->updateOrCreate(
            ['ghl_order_id' => $orderId],
            [
                'ghl_client_id' => $client?->id,
                'ghl_contact_id' => $contactId !== '' ? $contactId : $client?->ghl_contact_id,
                'ghl_location_id' => $locationId !== '' ? $locationId : $client?->location?->ghl_id,
                'amount' => $amount,
                'currency' => $currency !== '' ? $currency : 'USD',
                'description' => $description,
                'source' => 'ghl_webhook',
                'status' => NmiPaymentOrder::STATUS_PENDING,
                'nmi_order_id' => 'ghl-order-'.$orderId,
                'webhook_payload' => $payload,
            ]
        );

        return response('ok', 200);
    }

    private function assertWebhookSecret(Request $request): void
    {
        $expected = (string) config('services.ghl.bridge_webhook_secret');
        if ($expected === '') {
            return;
        }

        $incoming = (string) $request->header('X-Bridge-Secret', '');
        abort_if($incoming === '' || ! hash_equals($expected, $incoming), 401, 'Invalid bridge webhook secret.');
    }
}
