<?php

namespace App\Services;

use App\Models\NmiPaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NmiGatewayService
{
    public function __construct(private GhlApiService $ghlApiService)
    {
    }

    public function chargeOrder(NmiPaymentOrder $order, array $paymentData): NmiPaymentOrder
    {
        $securityKey = (string) config('services.nmi.security_key');
        $apiUrl = (string) config('services.nmi.api_url');

        if ($securityKey === '' || $apiUrl === '') {
            throw new RuntimeException('Missing NMI credentials. Set NMI_SECURITY_KEY and NMI_API_URL in .env.');
        }

        $requestData = [
            'security_key' => $securityKey,
            'type' => 'sale',
            'amount' => number_format((float) $order->amount, 2, '.', ''),
            'currency' => $order->currency ?: 'USD',
            'orderid' => $order->nmi_order_id ?: 'order-'.$order->id,
            'order_description' => $order->description ?: 'GHL order',
            'customer_receipt' => 'false',
        ];

        $customerVaultId = trim((string) ($paymentData['customer_vault_id'] ?? ''));
        if ($customerVaultId !== '') {
            $requestData['customer_vault_id'] = $customerVaultId;
        } else {
            $ccNumber = trim((string) ($paymentData['cc_number'] ?? ''));
            $ccExp = trim((string) ($paymentData['cc_exp'] ?? ''));
            if ($ccNumber === '' || $ccExp === '') {
                throw new RuntimeException('Provide customer_vault_id or test card fields (cc_number and cc_exp).');
            }
            $requestData['ccnumber'] = $ccNumber;
            $requestData['ccexp'] = $ccExp;
            $ccCvv = trim((string) ($paymentData['cc_cvv'] ?? ''));
            if ($ccCvv !== '') {
                $requestData['cvv'] = $ccCvv;
            }
        }

        if ($order->client?->email) {
            $requestData['email'] = $order->client->email;
        }

        if ($order->client?->name) {
            $requestData['firstname'] = $order->client->name;
        }

        $response = Http::asForm()->post($apiUrl, $requestData);

        if (! $response->successful()) {
            throw new RuntimeException('NMI request failed with HTTP '.$response->status().'.');
        }

        parse_str((string) $response->body(), $parsed);

        if (! is_array($parsed) || $parsed === []) {
            throw new RuntimeException('Unexpected NMI response format.');
        }

        $responseCode = (string) ($parsed['response_code'] ?? $parsed['response'] ?? '');
        $responseText = (string) ($parsed['responsetext'] ?? $parsed['response_text'] ?? '');

        $status = $this->mapGatewayStatus($responseCode, $responseText);

        $order->fill([
            'status' => $status,
            'nmi_transaction_id' => (string) ($parsed['transactionid'] ?? $order->nmi_transaction_id),
            'nmi_order_id' => (string) ($parsed['orderid'] ?? 'order-'.$order->id),
            'response_message' => $responseText !== '' ? $responseText : strtoupper((string) ($parsed['response'] ?? '')),
            'gateway_payload' => $parsed,
        ]);
        $order->save();

        return $order;
    }

    /**
     * Register an open invoice in NMI (invoicing=add_invoice). This is not a card charge:
     * the invoice appears in NMI as payable until the customer pays it there (or you run a sale).
     */
    public function registerGatewayInvoice(NmiPaymentOrder $order): void
    {
        if (! config('services.nmi.sync_ghl_invoices', true)) {
            return;
        }

        if ($order->source !== 'ghl_webhook') {
            return;
        }

        if ($order->nmi_invoice_id) {
            return;
        }

        $securityKey = (string) config('services.nmi.security_key');
        $apiUrl = (string) config('services.nmi.api_url');

        if ($securityKey === '' || $apiUrl === '') {
            Log::warning('Skipped NMI add_invoice: missing NMI credentials.', ['order_id' => $order->id]);

            return;
        }

        $email = trim((string) ($order->client?->email ?? ''));
        if ($email === '') {
            // Prioridad: email del cliente sync en ghl_clients; si no hay, .env NMI_INVOICE_FALLBACK_EMAIL (ver config/services.php nmi.invoice_fallback_email).
            $email = trim((string) config('services.nmi.invoice_fallback_email'));
        }

        if ($email === '') {
            Log::warning('Skipped NMI add_invoice: no usable email (client, NMI_INVOICE_FALLBACK_EMAIL, nor config default).', [
                'order_id' => $order->id,
                'ghl_order_id' => $order->ghl_order_id,
            ]);

            return;
        }

        $orderIdRef = $order->nmi_order_id
            ?: ($order->ghl_order_id ? 'ghl-order-'.$order->ghl_order_id : null)
            ?: ($order->ghl_invoice_id ? 'ghl-invoice-'.$order->ghl_invoice_id : null)
            ?: ('order-'.$order->id);

        $name = trim((string) ($order->client?->name ?? ''));
        $firstName = $name;
        $lastName = '';
        if ($name !== '' && str_contains($name, ' ')) {
            $parts = preg_split('/\s+/', $name, 2);
            $firstName = (string) ($parts[0] ?? $name);
            $lastName = (string) ($parts[1] ?? '');
        }

        $requestData = [
            'security_key' => $securityKey,
            'invoicing' => 'add_invoice',
            'amount' => number_format((float) $order->amount, 2, '.', ''),
            'email' => $email,
            'orderid' => $orderIdRef,
            'order_description' => $order->description ?: 'GHL invoice bridge',
            'currency' => $order->currency ?: 'USD',
        ];

        if ($firstName !== '') {
            $requestData['first_name'] = $firstName;
        }
        if ($lastName !== '') {
            $requestData['last_name'] = $lastName;
        }

        $response = Http::asForm()->post($apiUrl, $requestData);

        if (! $response->successful()) {
            Log::warning('NMI add_invoice HTTP error', [
                'order_id' => $order->id,
                'status' => $response->status(),
            ]);
            $order->fill([
                'response_message' => 'NMI invoice register: HTTP '.$response->status(),
            ]);
            $order->save();

            return;
        }

        parse_str((string) $response->body(), $parsed);

        if (! is_array($parsed) || $parsed === []) {
            Log::warning('NMI add_invoice empty response', ['order_id' => $order->id]);

            return;
        }

        $responseCode = (string) ($parsed['response_code'] ?? '');
        $legacyResponse = (string) ($parsed['response'] ?? '');
        $invoiceId = (string) ($parsed['invoice_id'] ?? '');
        $responseText = (string) ($parsed['responsetext'] ?? $parsed['response_text'] ?? '');

        $ok = $responseCode === '1' || $responseCode === '100' || $legacyResponse === '1';

        $priorGateway = (array) ($order->gateway_payload ?? []);

        if ($ok && $invoiceId !== '') {
            $order->fill([
                'nmi_invoice_id' => $invoiceId,
                'response_message' => $responseText !== '' ? $responseText : 'NMI invoice created',
                'gateway_payload' => array_merge($priorGateway, [
                    'add_invoice_response' => $parsed,
                ]),
            ]);
            $order->save();

            return;
        }

        $order->fill([
            'response_message' => $responseText !== '' ? $responseText : 'NMI add_invoice failed',
            'gateway_payload' => array_merge($priorGateway, [
                'add_invoice_response' => $parsed,
            ]),
        ]);
        $order->save();

        Log::warning('NMI add_invoice declined or missing invoice_id', [
            'order_id' => $order->id,
            'response_code' => $responseCode,
            'legacy_response' => $legacyResponse,
        ]);
    }

    public function handleWebhook(Request $request): ?NmiPaymentOrder
    {
        $payload = $this->extractWebhookPayload($request);
        $this->assertValidSignature($request);

        $transactionId = (string) (
            data_get($payload, 'transaction.id')
            ?? data_get($payload, 'data.transaction.id')
            ?? data_get($payload, 'event_body.transaction_id')
            ?? data_get($payload, 'event_body.transaction.id')
            ?? data_get($payload, 'transactionid')
            ?? data_get($payload, 'transaction_id')
            ?? data_get($payload, 'id')
            ?? ''
        );

        $orderId = (string) (
            data_get($payload, 'transaction.orderid')
            ?? data_get($payload, 'data.transaction.orderid')
            ?? data_get($payload, 'event_body.order_id')
            ?? data_get($payload, 'event_body.orderid')
            ?? data_get($payload, 'event_body.transaction.order_id')
            ?? data_get($payload, 'orderid')
            ?? data_get($payload, 'order_id')
            ?? data_get($payload, 'orderId')
            ?? data_get($payload, 'transaction.order_id')
            ?? data_get($payload, 'data.transaction.order_id')
            ?? ''
        );
        $invoiceId = (string) (
            data_get($payload, 'invoice_id')
            ?? data_get($payload, 'data.invoice_id')
            ?? data_get($payload, 'invoice.id')
            ?? data_get($payload, 'data.invoice.id')
            ?? data_get($payload, 'event_body.invoice_id')
            ?? data_get($payload, 'event_body.invoice.id')
            ?? data_get($payload, 'invoiceId')
            ?? ''
        );
        $ghlOrderIdCandidate = $this->normalizeGhlOrderId($orderId);

        Log::info('NMI webhook received for reconciliation', [
            'event' => data_get($payload, 'event') ?? data_get($payload, 'event_type'),
            'transaction_id' => $transactionId,
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'ghl_order_candidate' => $ghlOrderIdCandidate,
            'payload_keys' => array_keys($payload),
        ]);

        $order = null;
        if ($transactionId !== '') {
            $order = NmiPaymentOrder::query()
                ->where('nmi_transaction_id', $transactionId)
                ->latest()
                ->first();
        }

        if (! $order && $orderId !== '') {
            $trimOrderId = trim($orderId);
            $order = NmiPaymentOrder::query()
                ->where('nmi_order_id', $trimOrderId)
                ->when(preg_match('/^ghl-order-(.+)$/i', $trimOrderId, $orderMatch) === 1, function ($query) use ($orderMatch) {
                    $query->orWhere('ghl_order_id', $orderMatch[1]);
                })
                ->when(preg_match('/^ghl-invoice-(.+)$/i', $trimOrderId, $invoiceMatch) === 1, function ($query) use ($invoiceMatch) {
                    $query->orWhere('ghl_invoice_id', $invoiceMatch[1]);
                })
                ->when($ghlOrderIdCandidate !== '', function ($query) use ($ghlOrderIdCandidate) {
                    $query->orWhere('ghl_order_id', $ghlOrderIdCandidate);
                })
                ->latest()
                ->first();
        }

        if (! $order && $invoiceId !== '') {
            $order = NmiPaymentOrder::query()
                ->where('nmi_invoice_id', $invoiceId)
                ->latest()
                ->first();
        }

        $normalizedStatus = strtolower((string) (
            data_get($payload, 'status')
            ?? data_get($payload, 'transaction.status')
            ?? data_get($payload, 'data.transaction.status')
            ?? data_get($payload, 'event_body.condition')
            ?? data_get($payload, 'event_body.status')
            ?? ''
        ));
        $responseCode = (string) (
            data_get($payload, 'response_code')
            ?? data_get($payload, 'response.code')
            ?? data_get($payload, 'transaction.response_code')
            ?? data_get($payload, 'data.transaction.response_code')
            ?? data_get($payload, 'event_body.response_code')
            ?? ''
        );
        $responseText = strtolower((string) (
            data_get($payload, 'responsetext')
            ?? data_get($payload, 'response_text')
            ?? data_get($payload, 'event_body.responsetext')
            ?? data_get($payload, 'event_body.response_text')
            ?? ''
        ));
        $event = strtolower((string) (data_get($payload, 'event') ?? data_get($payload, 'event_type') ?? ''));
        $status = $this->resolveWebhookStatus($event, $normalizedStatus, $responseCode, $responseText);

        if (! $order && config('services.nmi.auto_create_from_webhook', false) && $status === NmiPaymentOrder::STATUS_APPROVED) {
            $order = NmiPaymentOrder::query()->create([
                'amount' => $this->extractWebhookAmount($payload),
                'currency' => $this->extractWebhookCurrency($payload),
                'description' => (string) (
                    data_get($payload, 'event_body.order_description')
                    ?? data_get($payload, 'transaction.order_description')
                    ?? 'Imported from NMI webhook'
                ),
                'source' => 'nmi_webhook',
                'status' => NmiPaymentOrder::STATUS_PENDING,
                'nmi_transaction_id' => $transactionId !== '' ? $transactionId : null,
                'nmi_order_id' => $orderId !== '' ? $orderId : null,
                'nmi_invoice_id' => $invoiceId !== '' ? $invoiceId : null,
                'response_message' => $responseText !== '' ? $responseText : strtoupper($event ?: 'APPROVED'),
                'webhook_payload' => $payload,
            ]);
        }

        if (! $order) {
            Log::info('NMI webhook received but order not found for reconciliation', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'event' => data_get($payload, 'event') ?? data_get($payload, 'event_type'),
                'payload_keys' => array_keys($payload),
            ]);

            return null;
        }

        $order->fill([
            'status' => $status,
            'nmi_transaction_id' => $transactionId !== '' ? $transactionId : $order->nmi_transaction_id,
            'nmi_order_id' => $orderId !== '' ? $orderId : $order->nmi_order_id,
            'nmi_invoice_id' => $invoiceId !== '' ? $invoiceId : $order->nmi_invoice_id,
            'webhook_payload' => $payload,
        ]);
        $order->save();

        if ($status === NmiPaymentOrder::STATUS_APPROVED) {
            $this->syncApprovedPaymentToGhl($order);
        }

        return $order;
    }

    private function resolveWebhookStatus(string $event, string $normalizedStatus, string $responseCode, string $responseText): string
    {
        if (str_contains($event, 'refund.success')) {
            return NmiPaymentOrder::STATUS_REFUNDED;
        }

        if (str_contains($event, 'void.success')) {
            return NmiPaymentOrder::STATUS_VOIDED;
        }

        if (
            str_contains($event, 'sale.success')
            || str_contains($event, 'capture.success')
            || str_contains($event, 'auth.success')
            || str_contains($event, 'invoice.paid')
            || str_contains($event, 'invoice.payment.success')
            || $normalizedStatus === 'approved'
            || $normalizedStatus === 'paid'
            || $normalizedStatus === 'complete'
            || $responseCode === '1'
            || $responseCode === '100'
            || str_contains($responseText, 'paid')
            || str_contains($responseText, 'approved')
            || str_contains($responseText, 'success')
        ) {
            return NmiPaymentOrder::STATUS_APPROVED;
        }

        if (
            str_contains($event, 'sale.failure')
            || str_contains($event, 'capture.failure')
            || str_contains($event, 'auth.failure')
            || $normalizedStatus === 'declined'
            || $normalizedStatus === 'failed'
            || $responseCode === '2'
            || $responseCode === '300'
        ) {
            return NmiPaymentOrder::STATUS_DECLINED;
        }

        return NmiPaymentOrder::STATUS_ERROR;
    }

    private function extractWebhookAmount(array $payload): float
    {
        $raw = data_get($payload, 'event_body.action.amount')
            ?? data_get($payload, 'event_body.requested_amount')
            ?? data_get($payload, 'amount')
            ?? data_get($payload, 'transaction.amount')
            ?? 0;

        return (float) $raw;
    }

    private function extractWebhookCurrency(array $payload): string
    {
        $currency = (string) (
            data_get($payload, 'event_body.currency')
            ?? data_get($payload, 'currency')
            ?? data_get($payload, 'transaction.currency')
            ?? 'USD'
        );

        $currency = strtoupper(trim($currency));

        return $currency !== '' ? $currency : 'USD';
    }

    private function normalizeGhlOrderId(string $orderId): string
    {
        $candidate = strtolower(trim($orderId));
        if ($candidate === '') {
            return '';
        }

        if (str_starts_with($candidate, 'ghl-order-')) {
            $candidate = substr($candidate, strlen('ghl-order-'));
        }

        if ($candidate !== '' && preg_match('/^\d+$/', $candidate) === 1) {
            $candidate = ltrim($candidate, '0');
        }

        return $candidate === '' ? '0' : $candidate;
    }

    private function extractWebhookPayload(Request $request): array
    {
        $payload = $request->all();
        if (is_array($payload) && $payload !== []) {
            return $payload;
        }

        $rawBody = trim((string) $request->getContent());
        if ($rawBody === '') {
            return [];
        }

        $decodedJson = json_decode($rawBody, true);
        if (is_array($decodedJson) && $decodedJson !== []) {
            return $decodedJson;
        }

        parse_str($rawBody, $parsed);

        return is_array($parsed) ? $parsed : [];
    }

    private function assertValidSignature(Request $request): void
    {
        $signingKey = (string) config('services.nmi.webhook_signing_key');
        if ($signingKey === '') {
            return;
        }

        $incoming = (string) ($request->header('Webhook-Signature') ?? $request->header('X-Webhook-Signature') ?? '');
        if ($incoming === '') {
            return;
        }

        $rawBody = (string) $request->getContent();
        if (preg_match('/t=(.*),s=(.*)/', $incoming, $matches) === 1) {
            $expected = hash_hmac('sha256', $matches[1].'.'.$rawBody, $signingKey);
            if (! hash_equals($expected, $matches[2])) {
                throw new RuntimeException('Invalid NMI webhook signature.');
            }

            return;
        }

        // Backward-compatible fallback for simple signature formats.
        $expected = hash_hmac('sha256', $rawBody, $signingKey);
        if (! hash_equals($expected, $incoming)) {
            throw new RuntimeException('Invalid NMI webhook signature.');
        }
    }

    private function mapGatewayStatus(string $responseCode, string $responseText): string
    {
        $normalizedCode = trim($responseCode);
        $normalizedText = strtolower(trim($responseText));

        if ($normalizedCode === '1' || str_contains($normalizedText, 'approved') || $normalizedText === 'success') {
            return NmiPaymentOrder::STATUS_APPROVED;
        }

        if ($normalizedCode === '2' || str_contains($normalizedText, 'declin')) {
            return NmiPaymentOrder::STATUS_DECLINED;
        }

        return NmiPaymentOrder::STATUS_ERROR;
    }

    private function syncApprovedPaymentToGhl(NmiPaymentOrder $order): void
    {
        if (! config('services.nmi.sync_approved_to_ghl', true)) {
            return;
        }

        $ghlInvoiceId = trim((string) ($order->ghl_invoice_id ?? ''));
        $ghlOrderId = trim((string) ($order->ghl_order_id ?? ''));

        if ($ghlInvoiceId === '' && $ghlOrderId === '') {
            return;
        }

        if ($order->synced_to_ghl_at) {
            return;
        }

        try {
            $paymentPayload = [
                'amount' => $order->amount,
                'transaction_id' => $order->nmi_transaction_id,
                'note' => 'Recorded from NMI bridge (Laravel).',
            ];

            if ($ghlInvoiceId !== '') {
                $this->ghlApiService->recordInvoicePayment($ghlInvoiceId, $paymentPayload);
            } elseif ($ghlOrderId !== '') {
                $this->ghlApiService->recordOrderPayment($ghlOrderId, $paymentPayload);
            }

            $order->synced_to_ghl_at = now();
            $order->ghl_sync_error = null;
            $order->save();
        } catch (\Throwable $exception) {
            $order->ghl_sync_error = $exception->getMessage();
            $order->save();
            Log::warning('Failed syncing NMI payment to GHL', [
                'order_id' => $order->id,
                'ghl_order_id' => $order->ghl_order_id,
                'ghl_invoice_id' => $order->ghl_invoice_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
