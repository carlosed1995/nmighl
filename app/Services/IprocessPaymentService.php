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
            ?? data_get($body, 'event_body.transaction_id')
            ?? ''
        ));
        if ($transactionId === '') {
            throw new RuntimeException('iProcess webhook is missing transaction_id.');
        }

        $amount = (float) (
            data_get($body, 'amount')
            ?? data_get($body, 'total')
            ?? data_get($body, 'event_body.action.amount')
            ?? data_get($body, 'event_body.requested_amount')
            ?? 0
        );
        if ($amount <= 0) {
            throw new RuntimeException('iProcess webhook amount must be greater than zero.');
        }

        $currency = strtoupper(trim((string) (
            data_get($body, 'currency')
            ?? data_get($body, 'event_body.currency')
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
            'contact_id' => trim((string) (
                data_get($body, 'contact_id')
                ?? data_get($body, 'contactId')
                ?? data_get($body, 'event_body.contact_id')
                ?? data_get($body, 'event_body.contactId')
                ?? ''
            )),
            'name' => trim((string) (
                data_get($body, 'customer.name')
                ?? data_get($body, 'name')
                ?? trim((string) (
                    data_get($body, 'event_body.billing_address.first_name', '').' '.data_get($body, 'event_body.billing_address.last_name', '')
                ))
                ?? ''
            )),
            'email' => trim((string) (
                data_get($body, 'customer.email')
                ?? data_get($body, 'email')
                ?? data_get($body, 'event_body.billing_address.email')
                ?? ''
            )),
            'phone' => trim((string) (
                data_get($body, 'customer.phone')
                ?? data_get($body, 'phone')
                ?? data_get($body, 'event_body.billing_address.phone')
                ?? data_get($body, 'event_body.billing_address.cell_phone')
                ?? ''
            )),
        ];

        $client = $this->resolveOrCreateClient($location, $contactData);

        $description = trim((string) (
            data_get($body, 'description')
            ?? data_get($body, 'note')
            ?? data_get($body, 'event_body.order_description')
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

        if (! (bool) config('services.iprocess.sync_invoice_to_ghl', true)) {
            $order->ghl_sync_error = null;
            $order->save();

            Log::info('iProcess webhook configured to skip GHL invoice sync', [
                'transaction_id' => $transactionId,
                'order_id' => $order->id,
                'ghl_location_id' => $locationId,
            ]);

            return $order;
        }

        try {
            $invoice = $this->ghlApiService->createInvoice($locationId, [
                'prefer_oauth_token' => true,
                'contact_id' => $client?->ghl_contact_id,
                'contact_name' => $client?->name,
                'contact_email' => $client?->email,
                'contact_phone' => $client?->phone,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'name' => 'New Invoice',
            ]);
            $invoiceId = (string) ($invoice['id'] ?? $invoice['_id'] ?? '');
            if ($invoiceId === '') {
                throw new RuntimeException('GHL createInvoice response did not include invoice id.');
            }

            if ((bool) config('services.iprocess.mark_invoice_paid_in_ghl', true)) {
                $this->ghlApiService->recordInvoicePayment($invoiceId, [
                    'prefer_oauth_token' => true,
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                    'location_id' => $locationId,
                    'notes' => 'Payment recorded from iProcess webhook',
                    'mode' => 'card',
                    'source' => 'iprocess-webhook',
                ]);
            }

            $order->ghl_invoice_id = $invoiceId;
            $order->synced_to_ghl_at = (bool) config('services.iprocess.mark_invoice_paid_in_ghl', true) ? now() : null;
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
        $existingClient = null;
        if ($ghlContactId !== '') {
            $existingClient = (clone $query)->where('ghl_contact_id', $ghlContactId)->first();
        }
        if (! $existingClient && $email !== '') {
            $existingClient = (clone $query)->whereRaw('LOWER(email) = ?', [$email])->first();
        }
        if (! $existingClient && $phone !== '') {
            $existingClient = (clone $query)->where('phone', $phone)->first();
        }
        if ($existingClient) {
            $existingContactId = trim((string) $existingClient->ghl_contact_id);
            if ($existingContactId !== '') {
                return $existingClient;
            }
        }

        $fallbackContactId = trim((string) config('services.iprocess.fallback_ghl_contact_id', ''));
        $missingContactInputs = $ghlContactId === '' && $email === '' && $phone === '';
        if ($missingContactInputs && $fallbackContactId !== '') {
            Log::info('Using configured fallback GHL contact for iProcess webhook without contact data', [
                'location_id' => $location->ghl_id,
                'fallback_contact_id' => $fallbackContactId,
            ]);

            $contact = $this->ghlApiService->getContact($fallbackContactId, $location->ghl_id, true);
            $ghlContactId = (string) ($contact['id'] ?? $contact['_id'] ?? $fallbackContactId);

            $attributes = [
                'name' => (string) ($contact['name'] ?? $name),
                'email' => (string) ($contact['email'] ?? null),
                'phone' => (string) ($contact['phone'] ?? null),
                'raw' => $contact,
            ];

            if ($existingClient) {
                $existingClient->ghl_contact_id = $ghlContactId;
                $existingClient->fill($attributes);
                $existingClient->save();

                return $existingClient;
            }

            return GhlClient::query()->updateOrCreate(
                [
                    'ghl_location_id' => $location->id,
                    'ghl_contact_id' => $ghlContactId,
                ],
                $attributes
            );
        }

        try {
            $contact = $this->ghlApiService->createContact($location->ghl_id, [
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
            ]);
            $ghlContactId = (string) ($contact['id'] ?? $contact['_id'] ?? $ghlContactId);
            if ($ghlContactId === '') {
                throw new RuntimeException('GHL contact creation returned no contact id.');
            }
        } catch (\Throwable $exception) {
            if ($fallbackContactId === '') {
                throw $exception;
            }

            Log::warning('Falling back to configured GHL contact for iProcess webhook', [
                'location_id' => $location->ghl_id,
                'fallback_contact_id' => $fallbackContactId,
                'reason' => $exception->getMessage(),
            ]);

            $contact = $this->ghlApiService->getContact($fallbackContactId, $location->ghl_id, true);
            $ghlContactId = (string) ($contact['id'] ?? $contact['_id'] ?? $fallbackContactId);
        }

        $attributes = [
            'name' => (string) ($contact['name'] ?? $name),
            'email' => (string) ($contact['email'] ?? ($email !== '' ? $email : null)),
            'phone' => (string) ($contact['phone'] ?? ($phone !== '' ? $phone : null)),
            'raw' => $contact,
        ];

        if ($existingClient) {
            $existingClient->ghl_contact_id = $ghlContactId;
            $existingClient->fill($attributes);
            $existingClient->save();

            return $existingClient;
        }

        return GhlClient::query()->updateOrCreate(
            [
                'ghl_location_id' => $location->id,
                'ghl_contact_id' => $ghlContactId,
            ],
            $attributes
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
