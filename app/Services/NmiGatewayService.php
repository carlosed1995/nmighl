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

        $orderIdRef = $order->nmi_order_id ?: ('ghl-order-'.($order->ghl_order_id ?? (string) $order->id));

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
        $payload = $request->all();
        $this->assertValidSignature($request);

        $transactionId = (string) (
            data_get($payload, 'transaction.id')
            ?? data_get($payload, 'data.transaction.id')
            ?? data_get($payload, 'transactionid')
            ?? ''
        );

        $orderId = (string) (
            data_get($payload, 'transaction.orderid')
            ?? data_get($payload, 'data.transaction.orderid')
            ?? data_get($payload, 'orderid')
            ?? ''
        );

        $order = null;
        if ($transactionId !== '') {
            $order = NmiPaymentOrder::query()
                ->where('nmi_transaction_id', $transactionId)
                ->latest()
                ->first();
        }

        if (! $order && $orderId !== '') {
            $order = NmiPaymentOrder::query()
                ->where('nmi_order_id', $orderId)
                ->orWhere('ghl_order_id', str_replace('ghl-order-', '', $orderId))
                ->latest()
                ->first();
        }

        if (! $order) {
            return null;
        }

        $event = strtolower((string) (data_get($payload, 'event') ?? data_get($payload, 'event_type') ?? ''));
        $status = $order->status;

        if (str_contains($event, 'refund.success')) {
            $status = NmiPaymentOrder::STATUS_REFUNDED;
        } elseif (str_contains($event, 'void.success')) {
            $status = NmiPaymentOrder::STATUS_VOIDED;
        } elseif (str_contains($event, 'sale.success') || str_contains($event, 'capture.success') || str_contains($event, 'auth.success')) {
            $status = NmiPaymentOrder::STATUS_APPROVED;
        } elseif (str_contains($event, 'sale.failure') || str_contains($event, 'capture.failure') || str_contains($event, 'auth.failure')) {
            $status = NmiPaymentOrder::STATUS_DECLINED;
        }

        $order->fill([
            'status' => $status,
            'nmi_transaction_id' => $transactionId !== '' ? $transactionId : $order->nmi_transaction_id,
            'nmi_order_id' => $orderId !== '' ? $orderId : $order->nmi_order_id,
            'webhook_payload' => $payload,
        ]);
        $order->save();

        if ($status === NmiPaymentOrder::STATUS_APPROVED) {
            $this->syncApprovedPaymentToGhl($order);
        }

        return $order;
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
        if (! $order->ghl_order_id) {
            return;
        }

        if ($order->synced_to_ghl_at) {
            return;
        }

        try {
            $this->ghlApiService->recordOrderPayment($order->ghl_order_id, [
                'amount' => $order->amount,
                'transaction_id' => $order->nmi_transaction_id,
                'note' => 'Recorded from NMI bridge (Laravel).',
            ]);

            $order->synced_to_ghl_at = now();
            $order->ghl_sync_error = null;
            $order->save();
        } catch (\Throwable $exception) {
            $order->ghl_sync_error = $exception->getMessage();
            $order->save();
            Log::warning('Failed syncing NMI payment to GHL', [
                'order_id' => $order->id,
                'ghl_order_id' => $order->ghl_order_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
