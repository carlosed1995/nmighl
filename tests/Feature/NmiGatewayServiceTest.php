<?php

namespace Tests\Feature;

use App\Models\NmiPaymentOrder;
use App\Services\GhlApiService;
use App\Services\NmiGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NmiGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_paid_webhook_matches_by_invoice_id_and_syncs_to_ghl(): void
    {
        $order = NmiPaymentOrder::query()->create([
            'amount' => 44.44,
            'currency' => 'USD',
            'description' => 'Invoice from webhook',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000013',
            'nmi_order_id' => 'ghl-order-000013',
            'nmi_invoice_id' => 'INV-12345',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock
            ->shouldReceive('recordOrderPayment')
            ->once()
            ->with('000013', \Mockery::on(function (array $payload): bool {
                return $payload['amount'] == 44.44
                    && $payload['transaction_id'] === 'TXN-999'
                    && str_contains((string) ($payload['note'] ?? ''), 'NMI bridge');
            }));

        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event' => 'invoice.paid',
            'invoice_id' => 'INV-12345',
            'transactionid' => 'TXN-999',
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertSame('TXN-999', $order->nmi_transaction_id);
        $this->assertNotNull($order->synced_to_ghl_at);
        $this->assertNull($order->ghl_sync_error);
    }

    public function test_register_gateway_invoice_uses_fallback_email_when_client_email_missing(): void
    {
        config()->set('services.nmi.security_key', 'test-security-key');
        config()->set('services.nmi.api_url', 'https://secure.networkmerchants.com/api/transact.php');
        config()->set('services.nmi.invoice_fallback_email', 'ops@example.com');

        Http::fake([
            'https://secure.networkmerchants.com/api/transact.php' => Http::response(
                'response=1&response_code=100&invoice_id=123456789&responsetext=Invoice+Sent',
                200
            ),
        ]);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 19.99,
            'currency' => 'USD',
            'description' => 'Needs invoice',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000050',
            'nmi_order_id' => 'ghl-order-000050',
        ]);

        $this->mock(GhlApiService::class);
        $service = app(NmiGatewayService::class);
        $service->registerGatewayInvoice($order);

        $order->refresh();
        $this->assertSame('123456789', $order->nmi_invoice_id);
        $this->assertStringContainsString('Invoice Sent', (string) $order->response_message);

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return $data['invoicing'] === 'add_invoice'
                && $data['email'] === 'ops@example.com'
                && $data['orderid'] === 'ghl-order-000050';
        });
    }

    public function test_handle_webhook_throws_on_invalid_signature_when_signing_key_set(): void
    {
        config()->set('services.nmi.webhook_signing_key', 'super-secret');

        $this->mock(GhlApiService::class);
        $service = app(NmiGatewayService::class);

        $request = Request::create('/webhooks/nmi', 'POST', [
            'event' => 'sale.success',
            'orderid' => 'ghl-order-000013',
        ]);
        $request->headers->set('Webhook-Signature', 'invalid-signature');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid NMI webhook signature.');

        $service->handleWebhook($request);
    }

    public function test_webhook_with_raw_querystring_body_is_parsed_and_approved(): void
    {
        $order = NmiPaymentOrder::query()->create([
            'amount' => 88.88,
            'currency' => 'USD',
            'description' => 'Invoice from webhook',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000015',
            'nmi_order_id' => 'ghl-order-000015',
            'nmi_invoice_id' => '11997168758',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock
            ->shouldReceive('recordOrderPayment')
            ->once();

        $service = app(NmiGatewayService::class);
        $request = Request::create(
            '/webhooks/nmi',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            'transactionid=11997170371&orderid=ghl-order-000015&response_code=100&responsetext=Approved'
        );

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertSame('11997170371', $order->nmi_transaction_id);
        $this->assertNotNull($order->synced_to_ghl_at);
    }

    public function test_webhook_with_event_body_format_is_parsed_and_approved(): void
    {
        $order = NmiPaymentOrder::query()->create([
            'amount' => 77.77,
            'currency' => 'USD',
            'description' => 'Invoice paid in NMI',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000017',
            'nmi_order_id' => 'ghl-order-000017',
            'nmi_invoice_id' => '11997186471',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock
            ->shouldReceive('recordOrderPayment')
            ->once()
            ->with('000017', \Mockery::on(function (array $payload): bool {
                return $payload['amount'] == 77.77
                    && $payload['transaction_id'] === 'TXN-EVENT-BODY-1';
            }));

        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event_type' => 'transaction.sale.success',
            'event_body' => [
                'transaction_id' => 'TXN-EVENT-BODY-1',
                'order_id' => 'ghl-order-000017',
                'invoice_id' => '11997186471',
                'condition' => 'complete',
            ],
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertSame('TXN-EVENT-BODY-1', $order->nmi_transaction_id);
        $this->assertNotNull($order->synced_to_ghl_at);
        $this->assertNull($order->ghl_sync_error);
    }
}
