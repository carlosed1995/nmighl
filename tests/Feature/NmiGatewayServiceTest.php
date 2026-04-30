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

    public function test_approved_webhook_prefers_order_sync_when_both_ghl_ids_exist(): void
    {
        config()->set('services.nmi.sync_approved_to_ghl', true);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 77.77,
            'currency' => 'USD',
            'description' => 'Both IDs present',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000035',
            'ghl_invoice_id' => 'ghl-inv-both-1',
            'nmi_order_id' => 'ghl-order-000035',
            'nmi_invoice_id' => 'NMI-INV-BOTH-1',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock
            ->shouldReceive('recordOrderPayment')
            ->once()
            ->with('000035', \Mockery::on(function (array $payload): bool {
                return $payload['amount'] == 77.77
                    && $payload['transaction_id'] === 'TXN-BOTH-1'
                    && str_contains((string) ($payload['note'] ?? ''), 'NMI bridge');
            }));
        $ghlApiMock->shouldReceive('recordInvoicePayment')->never();

        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event' => 'invoice.paid',
            'transactionid' => 'TXN-BOTH-1',
            'orderid' => 'ghl-order-000035',
            'invoice_id' => 'NMI-INV-BOTH-1',
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertNotNull($order->synced_to_ghl_at);
        $this->assertNull($order->ghl_sync_error);
    }

    public function test_approved_webhook_falls_back_to_invoice_when_order_sync_forbidden(): void
    {
        config()->set('services.nmi.sync_approved_to_ghl', true);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 66.66,
            'currency' => 'USD',
            'description' => 'Order ID not accessible, fallback invoice',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000039',
            'ghl_invoice_id' => '69f2dc5fa82c6bc395797e',
            'nmi_order_id' => 'ghl-order-000039',
            'nmi_invoice_id' => 'NMI-INV-FALLBACK-1',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock
            ->shouldReceive('recordOrderPayment')
            ->once()
            ->andThrow(new RuntimeException('Failed to record payment in GHL order 000039 (status 403). Forbidden resource'));
        $ghlApiMock
            ->shouldReceive('recordInvoicePayment')
            ->once()
            ->with('69f2dc5fa82c6bc395797e', \Mockery::on(function (array $payload): bool {
                return $payload['amount'] == 66.66
                    && $payload['transaction_id'] === 'TXN-FALLBACK-1';
            }));

        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event' => 'invoice.paid',
            'transactionid' => 'TXN-FALLBACK-1',
            'orderid' => 'ghl-order-000039',
            'invoice_id' => 'NMI-INV-FALLBACK-1',
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertNotNull($order->synced_to_ghl_at);
        $this->assertNull($order->ghl_sync_error);
    }

    public function test_approved_webhook_calls_record_invoice_payment_when_ghl_invoice_id_set(): void
    {
        config()->set('services.nmi.sync_approved_to_ghl', true);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 55.55,
            'currency' => 'USD',
            'description' => 'GHL invoice only',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => null,
            'ghl_invoice_id' => 'ghl-inv-abc-123',
            'nmi_order_id' => 'ghl-invoice-ghl-inv-abc-123',
            'nmi_invoice_id' => 'NMI-INV-1',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock->shouldReceive('recordOrderPayment')->never();
        $ghlApiMock
            ->shouldReceive('recordInvoicePayment')
            ->once()
            ->with('ghl-inv-abc-123', \Mockery::on(function (array $payload): bool {
                return $payload['amount'] == 55.55
                    && $payload['transaction_id'] === 'TXN-INV-1'
                    && str_contains((string) ($payload['note'] ?? ''), 'NMI bridge');
            }));

        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event_type' => 'transaction.sale.success',
            'event_body' => [
                'transaction_id' => 'TXN-INV-1',
                'order_id' => 'ghl-invoice-ghl-inv-abc-123',
                'invoice_id' => 'NMI-INV-1',
                'condition' => 'complete',
                'action' => ['response_code' => '100', 'response_text' => 'SUCCESS', 'amount' => '55.55'],
            ],
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertNotNull($order->synced_to_ghl_at);
        $this->assertNull($order->ghl_sync_error);
    }

    public function test_invoice_paid_webhook_matches_by_invoice_id_and_syncs_to_ghl(): void
    {
        config()->set('services.nmi.sync_approved_to_ghl', true);

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

    public function test_register_gateway_invoice_uses_ghl_invoice_prefix_when_no_order(): void
    {
        config()->set('services.nmi.security_key', 'test-security-key');
        config()->set('services.nmi.api_url', 'https://secure.networkmerchants.com/api/transact.php');
        config()->set('services.nmi.invoice_fallback_email', 'ops@example.com');

        Http::fake([
            'https://secure.networkmerchants.com/api/transact.php' => Http::response(
                'response=1&response_code=100&invoice_id=999888777&responsetext=Invoice+Sent',
                200
            ),
        ]);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 12.34,
            'currency' => 'USD',
            'description' => 'GHL invoice bridge',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => null,
            'ghl_invoice_id' => 'inv-from-ghl-99',
            'nmi_order_id' => 'ghl-invoice-inv-from-ghl-99',
        ]);

        $this->mock(GhlApiService::class);
        $service = app(NmiGatewayService::class);
        $service->registerGatewayInvoice($order);

        $order->refresh();
        $this->assertSame('999888777', $order->nmi_invoice_id);

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return $data['invoicing'] === 'add_invoice'
                && $data['orderid'] === 'ghl-invoice-inv-from-ghl-99';
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
        config()->set('services.nmi.sync_approved_to_ghl', true);

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

    public function test_webhook_event_body_marks_order_approved_without_syncing_ghl_when_disabled(): void
    {
        config()->set('services.nmi.sync_approved_to_ghl', false);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 120.00,
            'currency' => 'USD',
            'description' => 'Invoice payment from NMI',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '000020',
            'nmi_order_id' => 'ghl-order-000020',
            'nmi_invoice_id' => '11998713814',
        ]);

        $ghlApiMock = $this->mock(GhlApiService::class);
        $ghlApiMock
            ->shouldReceive('recordOrderPayment')
            ->never();

        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event_type' => 'transaction.sale.success',
            'event_body' => [
                'transaction_id' => '11998799999',
                'order_id' => 'ghl-order-000020',
                'invoice_id' => '11998713814',
                'condition' => 'complete',
            ],
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertSame('11998799999', $order->nmi_transaction_id);
        $this->assertNull($order->synced_to_ghl_at);
    }

    public function test_webhook_matches_ghl_order_with_leading_zeroes_in_event_order_id(): void
    {
        config()->set('services.nmi.sync_approved_to_ghl', false);

        $order = NmiPaymentOrder::query()->create([
            'amount' => 133.00,
            'currency' => 'USD',
            'description' => 'Invoice paid in NMI VT',
            'status' => NmiPaymentOrder::STATUS_PENDING,
            'source' => 'ghl_webhook',
            'ghl_order_id' => '23',
            'nmi_order_id' => null,
            'nmi_invoice_id' => '12000269095',
        ]);

        $this->mock(GhlApiService::class);
        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event_type' => 'transaction.sale.success',
            'event_body' => [
                'transaction_id' => '12000278896',
                'order_id' => 'ghl-order-000023',
                'invoice_id' => '12000269095',
                'condition' => 'complete',
            ],
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $order->refresh();
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertSame('12000278896', $order->nmi_transaction_id);
        $this->assertSame('ghl-order-000023', $order->nmi_order_id);
    }

    public function test_webhook_can_auto_create_local_order_when_not_found(): void
    {
        config()->set('services.nmi.auto_create_from_webhook', true);
        config()->set('services.nmi.sync_approved_to_ghl', false);

        $this->mock(GhlApiService::class);
        $service = app(NmiGatewayService::class);
        $request = Request::create('/webhooks/nmi', 'POST', [
            'event_type' => 'transaction.sale.success',
            'event_body' => [
                'transaction_id' => 'AUTO-TXN-1001',
                'order_id' => 'nmi-order-xyz-1001',
                'invoice_id' => 'inv-1001',
                'condition' => 'complete',
                'currency' => 'USD',
                'action' => [
                    'amount' => '45.00',
                    'response_code' => '100',
                    'response_text' => 'SUCCESS',
                ],
                'order_description' => 'Direct NMI invoice paid',
            ],
        ]);

        $result = $service->handleWebhook($request);

        $this->assertNotNull($result);
        $this->assertSame(NmiPaymentOrder::STATUS_APPROVED, $result->status);
        $this->assertSame('AUTO-TXN-1001', $result->nmi_transaction_id);
        $this->assertSame('nmi-order-xyz-1001', $result->nmi_order_id);
        $this->assertSame('inv-1001', $result->nmi_invoice_id);
        $this->assertSame('nmi_webhook', $result->source);
        $this->assertSame('45.00', $result->amount);
    }

}
