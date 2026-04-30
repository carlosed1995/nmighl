<?php

namespace Tests\Feature;

use App\Models\NmiPaymentOrder;
use App\Services\IprocessPaymentService;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class IprocessWebhookControllerTest extends TestCase
{
    public function test_iprocess_webhook_returns_ok_response_when_service_succeeds(): void
    {
        $order = NmiPaymentOrder::query()->make([
            'id' => 999,
            'ghl_invoice_id' => 'inv-123',
        ]);

        $service = Mockery::mock(IprocessPaymentService::class);
        $service->shouldReceive('handleWebhook')->once()->andReturn($order);
        $this->app->instance(IprocessPaymentService::class, $service);

        $response = $this->postJson('/webhooks/iprocess/payments', [
            'transaction_id' => 'tx-1',
            'amount' => 10,
        ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'order_id' => 999,
            'ghl_invoice_id' => 'inv-123',
        ]);
    }

    public function test_iprocess_webhook_returns_422_when_service_throws_runtime_exception(): void
    {
        $service = Mockery::mock(IprocessPaymentService::class);
        $service->shouldReceive('handleWebhook')->once()->andThrow(new RuntimeException('Invalid iProcess webhook secret.'));
        $this->app->instance(IprocessPaymentService::class, $service);

        $response = $this->postJson('/webhooks/iprocess/payments', []);

        $response->assertStatus(422)->assertJson([
            'ok' => false,
            'error' => 'Invalid iProcess webhook secret.',
        ]);
    }
}
