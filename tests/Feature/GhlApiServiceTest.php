<?php

namespace Tests\Feature;

use App\Services\GhlApiService;
use App\Services\GhlOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GhlApiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_invoice_payment_sends_location_query_and_header(): void
    {
        config()->set('services.ghl.base_url', 'https://services.leadconnectorhq.com');
        config()->set('services.ghl.invoice_api_version', '2023-02-21');

        $oauthService = $this->mock(GhlOAuthService::class);
        $oauthService->shouldReceive('getAccessToken')->andReturn('oauth-token');

        Http::fake([
            'https://services.leadconnectorhq.com/*' => Http::response(['ok' => true], 200),
        ]);

        $service = app(GhlApiService::class);
        $service->recordInvoicePayment('inv-123', [
            'amount' => 10.5,
            'transaction_id' => 'txn-1',
            'location_id' => 'loc-123',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://services.leadconnectorhq.com/invoices/inv-123/record-payment?altId=loc-123&altType=location'
                && $request->hasHeader('Location-Id', 'loc-123')
                && $request->hasHeader('Version', '2023-02-21');
        });
    }

    public function test_record_order_payment_sends_location_query_and_header(): void
    {
        config()->set('services.ghl.base_url', 'https://services.leadconnectorhq.com');
        config()->set('services.ghl.api_version', '2021-07-28');

        $oauthService = $this->mock(GhlOAuthService::class);
        $oauthService->shouldReceive('getAccessToken')->andReturn('oauth-token');

        Http::fake([
            'https://services.leadconnectorhq.com/*' => Http::response(['ok' => true], 200),
        ]);

        $service = app(GhlApiService::class);
        $service->recordOrderPayment('order-123', [
            'amount' => 20,
            'transaction_id' => 'txn-2',
            'location_id' => 'loc-abc',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://services.leadconnectorhq.com/payments/orders/order-123/record-payment?altId=loc-abc&altType=location'
                && $request->hasHeader('Location-Id', 'loc-abc')
                && $request->hasHeader('Version', '2021-07-28');
        });
    }

    public function test_create_contact_prefers_private_integration_token_for_location_scoped_calls(): void
    {
        config()->set('services.ghl.base_url', 'https://services.leadconnectorhq.com');
        config()->set('services.ghl.agency_token', 'pit-subaccount-token');

        $oauthService = $this->mock(GhlOAuthService::class);
        $oauthService->shouldReceive('getAccessToken')->andReturn('oauth-token-should-not-be-used');

        Http::fake([
            'https://services.leadconnectorhq.com/*' => Http::response([
                'contact' => ['id' => 'contact-1', 'firstName' => 'Carlos'],
            ], 201),
        ]);

        $service = app(GhlApiService::class);
        $service->createContact('loc-123', [
            'name' => 'Carlos Test',
            'email' => 'carlos@example.com',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://services.leadconnectorhq.com/contacts/'
                && $request->hasHeader('Authorization', 'Bearer pit-subaccount-token')
                && $request['locationId'] === 'loc-123';
        });
    }
}
