<?php

namespace App\Services;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use App\Models\GhlOauthToken;
use App\Models\NmiPaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IprocessPaymentService
{
    public function __construct(private GhlApiService $ghlApiService)
    {
    }

    public function handleWebhook(Request $request): NmiPaymentOrder
    {
        $this->assertWebhookSecret($request);

        $payload = (array) ($request->all() ?: []);
        $body = (array) (data_get($payload, 'body') ?? $payload);

        $transactionId = trim((string) (
            data_get($body, 'transaction_id')
            ?? data_get($body, 'transactionId')
            ?? data_get($body, 'id')
            ?? data_get($body, 'reference')
            ?? ''
        ));
        if ($transactionId === '') {
            throw new RuntimeException('iProcess webhook is missing transaction_id.');
        }

        $amount = (float) (
            data_get($body, 'amount')
            ?? data_get($body, 'total')
            ?? 0
        );
        if ($amount <= 0) {
            throw new RuntimeException('iProcess webhook amount must be greater than zero.');
        }

        $currency = strtoupper(trim((string) (
            data_get($body, 'currency')
            ?? 'USD'
        )));
        if ($currency === '') {
            $currency = 'USD';
        }

        $locationId = $this->resolveLocationId($body);
        if ($locationId === '') {
            throw new RuntimeException('iProcess webhook is missing location context (locationId).');
        }

        $location = GhlLocation::query()->firstOrCreate(
            ['ghl_id' => $locationId],
            ['name' => 'iProcess Location '.$locationId]
        );

        $contactData = [
            'contact_id' => trim((string) (data_get($body, 'contact_id') ?? data_get($body, 'contactId') ?? '')),
            'name' => trim((string) (data_get($body, 'customer.name') ?? data_get($body, 'name') ?? '')),
            'email' => trim((string) (data_get($body, 'customer.email') ?? data_get($body, 'email') ?? '')),
            'phone' => trim((string) (data_get($body, 'customer.phone') ?? data_get($body, 'phone') ?? '')),
        ];

        $client = $this->resolveOrCreateClient($location, $contactData);

        $description = trim((string) (
            data_get($body, 'description')
            ?? data_get($body, 'note')
            ?? ('iProcess payment '.$transactionId)
        ));

        $order = NmiPaymentOrder::query()->updateOrCreate(
            ['nmi_transaction_id' => $transactionId],
            [
                'ghl_client_id' => $client?->id,
                'ghl_contact_id' => $client?->ghl_contact_id,
                'ghl_location_id' => $locationId,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'source' => 'iprocess_webhook',
                'status' => NmiPaymentOrder::STATUS_APPROVED,
                'response_message' => 'Imported from iProcess webhook.',
                'nmi_order_id' => 'iprocess-'.$transactionId,
                'webhook_payload' => $payload,
            ]
        );

        try {
            $invoice = $this->ghlApiService->createInvoice($locationId, [
                'contact_id' => $client?->ghl_contact_id,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'title' => 'Invoice',
                'name' => 'New Invoice',
            ]);
            $invoiceId = (string) ($invoice['id'] ?? $invoice['_id'] ?? '');
            if ($invoiceId === '') {
                throw new RuntimeException('GHL createInvoice response did not include invoice id.');
            }

            $this->ghlApiService->recordInvoicePayment($invoiceId, [
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'location_id' => $locationId,
                'notes' => 'Payment recorded from iProcess webhook',
                'mode' => 'card',
                'source' => 'iprocess-webhook',
            ]);

            $order->ghl_invoice_id = $invoiceId;
            $order->synced_to_ghl_at = now();
            $order->ghl_sync_error = null;
            $order->save();
        } catch (\Throwable $exception) {
            $order->ghl_sync_error = $exception->getMessage();
            $order->save();
            throw $exception;
        }

        Log::info('iProcess payment synced to GHL invoice successfully', [
            'transaction_id' => $transactionId,
            'order_id' => $order->id,
            'ghl_invoice_id' => $invoiceId,
            'ghl_location_id' => $locationId,
        ]);

        return $order;
    }

    private function resolveLocationId(array $payload): string
    {
        $locationId = trim((string) (
            data_get($payload, 'locationId')
            ?? data_get($payload, 'location_id')
            ?? data_get($payload, 'subAccountId')
            ?? config('services.iprocess.default_location_id')
            ?? GhlOauthToken::query()->latest('id')->value('location_id')
            ?? ''
        ));

        return $locationId;
    }

    private function resolveOrCreateClient(GhlLocation $location, array $contactData): ?GhlClient
    {
        $ghlContactId = trim((string) ($contactData['contact_id'] ?? ''));
        $email = strtolower(trim((string) ($contactData['email'] ?? '')));
        $phone = trim((string) ($contactData['phone'] ?? ''));
        $name = trim((string) ($contactData['name'] ?? ''));
        if ($name === '') {
            $name = 'iProcess Customer';
        }

        $query = GhlClient::query()->where('ghl_location_id', $location->id);
        if ($ghlContactId !== '') {
            $found = (clone $query)->where('ghl_contact_id', $ghlContactId)->first();
            if ($found) {
                return $found;
            }
        }
        if ($email !== '') {
            $found = (clone $query)->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($found) {
                return $found;
            }
        }
        if ($phone !== '') {
            $found = (clone $query)->where('phone', $phone)->first();
            if ($found) {
                return $found;
            }
        }

        $contact = $this->ghlApiService->createContact($location->ghl_id, [
            'name' => $name,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);
        $ghlContactId = (string) ($contact['id'] ?? $contact['_id'] ?? $ghlContactId);
        if ($ghlContactId === '') {
            throw new RuntimeException('GHL contact creation returned no contact id.');
        }

        return GhlClient::query()->updateOrCreate(
            [
                'ghl_location_id' => $location->id,
                'ghl_contact_id' => $ghlContactId,
            ],
            [
                'name' => (string) ($contact['name'] ?? $name),
                'email' => (string) ($contact['email'] ?? ($email !== '' ? $email : null)),
                'phone' => (string) ($contact['phone'] ?? ($phone !== '' ? $phone : null)),
                'raw' => $contact,
            ]
        );
    }

    private function assertWebhookSecret(Request $request): void
    {
        $expected = (string) config('services.iprocess.webhook_secret');
        if ($expected === '') {
            return;
        }

        $incoming = (string) ($request->header('X-iProcess-Secret') ?: $request->header('X-Bridge-Secret'));
        if ($incoming === '' || ! hash_equals($expected, $incoming)) {
            throw new RuntimeException('Invalid iProcess webhook secret.');
        }
    }
}
