<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\NmiPaymentOrder;
use App\Services\NmiGatewayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GhlBridgeWebhookController extends Controller
{
    public function __invoke(Request $request, NmiGatewayService $gatewayService): Response
    {
        $this->assertWebhookSecret($request);

        $payload = $request->all();
        $normalizedPayload = (array) (data_get($payload, 'body') ?? $payload);

        $ghlOrderId = trim((string) (
            data_get($normalizedPayload, 'orderId')
            ?? data_get($normalizedPayload, 'customData.orderId')
            ?? data_get($normalizedPayload, 'order.id')
            ?? ''
        ));
        $ghlInvoiceId = trim((string) (
            data_get($normalizedPayload, 'invoiceId')
            ?? data_get($normalizedPayload, 'invoice.id')
            ?? data_get($normalizedPayload, 'invoice._id')
            ?? data_get($normalizedPayload, 'invoice._data._id')
            ?? data_get($normalizedPayload, 'invoice._data.invoiceNumber')
            ?? data_get($normalizedPayload, 'invoice.invoiceNumber')
            ?? data_get($normalizedPayload, 'invoiceNumber')
            ?? ''
        ));
        $contactId = (string) (
            data_get($normalizedPayload, 'contactId')
            ?? data_get($normalizedPayload, 'customData.contactId')
            ?? data_get($normalizedPayload, 'contact_id')
            ?? data_get($normalizedPayload, 'contact.id')
            ?? data_get($normalizedPayload, 'invoice.contactDetails.id')
            ?? data_get($normalizedPayload, 'invoice._data.contactId')
            ?? ''
        );
        $contactEmail = trim((string) (
            data_get($normalizedPayload, 'email')
            ?? data_get($normalizedPayload, 'contact.email')
            ?? data_get($normalizedPayload, 'invoice.contactDetails.email')
            ?? data_get($normalizedPayload, 'invoice._data.contactEmail')
            ?? ''
        ));
        $locationId = (string) (
            data_get($normalizedPayload, 'locationId')
            ?? data_get($normalizedPayload, 'customData.locationId')
            ?? data_get($normalizedPayload, 'location_id')
            ?? data_get($normalizedPayload, 'location.id')
            ?? ''
        );

        $rawAmount = data_get($normalizedPayload, 'amount')
            ?? data_get($normalizedPayload, 'customData.amount')
            ?? data_get($normalizedPayload, 'order.amount')
            ?? data_get($normalizedPayload, 'invoice.amount')
            ?? data_get($normalizedPayload, 'invoice._data.total')
            ?? 0;
        $amount = (float) preg_replace('/[^\d.\-]/', '', (string) $rawAmount);

        $currency = strtoupper((string) (
            data_get($normalizedPayload, 'currency')
            ?? data_get($normalizedPayload, 'customData.currency')
            ?? data_get($normalizedPayload, 'order.currency')
            ?? data_get($normalizedPayload, 'invoice.currency')
            ?? data_get($normalizedPayload, 'invoice._data.currency')
            ?? 'USD'
        ));
        $description = (string) (
            data_get($normalizedPayload, 'description')
            ?? data_get($normalizedPayload, 'customData.description')
            ?? data_get($normalizedPayload, 'order.description')
            ?? data_get($normalizedPayload, 'invoice._data.name')
            ?? 'GHL order webhook'
        );

        if ($ghlOrderId === '' && $ghlInvoiceId === '') {
            return response('ignored', 202);
        }

        if ($amount <= 0) {
            return response('ignored', 202);
        }

        $client = null;
        if ($contactId !== '') {
            $client = GhlClient::query()->where('ghl_contact_id', $contactId)->first();
        }
        if (! $client && $contactEmail !== '') {
            $client = GhlClient::query()->whereRaw('LOWER(email) = ?', [strtolower($contactEmail)])->first();
        }

        $uniqueKey = $ghlOrderId !== ''
            ? ['ghl_order_id' => $ghlOrderId]
            : ['ghl_invoice_id' => $ghlInvoiceId];

        $nmiOrderRef = $ghlOrderId !== ''
            ? 'ghl-order-'.$ghlOrderId
            : 'ghl-invoice-'.$ghlInvoiceId;

        $order = NmiPaymentOrder::query()->updateOrCreate(
            $uniqueKey,
            [
                'ghl_client_id' => $client?->id,
                'ghl_contact_id' => $contactId !== '' ? $contactId : $client?->ghl_contact_id,
                'ghl_location_id' => $locationId !== '' ? $locationId : $client?->location?->ghl_id,
                'ghl_order_id' => $ghlOrderId !== '' ? $ghlOrderId : null,
                'ghl_invoice_id' => $ghlInvoiceId !== '' ? $ghlInvoiceId : null,
                'amount' => $amount,
                'currency' => $currency !== '' ? $currency : 'USD',
                'description' => $description,
                'source' => 'ghl_webhook',
                'status' => NmiPaymentOrder::STATUS_PENDING,
                'nmi_order_id' => $nmiOrderRef,
                'webhook_payload' => $normalizedPayload,
            ]
        );

        $order->load('client');
        $gatewayService->registerGatewayInvoice($order);

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
