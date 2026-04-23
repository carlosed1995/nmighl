<?php

namespace App\Services;

use App\Models\NmiPaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NmiGatewayService
{
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
            'orderid' => 'order-'.$order->id,
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

    public function handleWebhook(Request $request): void
    {
        $payload = $request->all();
        $this->assertValidSignature($request);

        $transactionId = (string) (
            data_get($payload, 'transaction.id')
            ?? data_get($payload, 'data.transaction.id')
            ?? data_get($payload, 'transactionid')
            ?? ''
        );

        if ($transactionId === '') {
            return;
        }

        $order = NmiPaymentOrder::query()
            ->where('nmi_transaction_id', $transactionId)
            ->latest()
            ->first();

        if (! $order) {
            return;
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
            'webhook_payload' => $payload,
        ]);
        $order->save();
    }

    private function assertValidSignature(Request $request): void
    {
        $signingKey = (string) config('services.nmi.webhook_signing_key');
        if ($signingKey === '') {
            return;
        }

        $incoming = (string) ($request->header('X-Webhook-Signature') ?? '');
        if ($incoming === '') {
            return;
        }

        $expected = hash_hmac('sha256', (string) $request->getContent(), $signingKey);
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
}
