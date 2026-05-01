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

    public function test_create_invoice_sends_required_payload_shape_for_2023_02_21(): void
    {
        config()->set('services.ghl.base_url', 'https://services.leadconnectorhq.com');
        config()->set('services.ghl.invoice_api_version', '2023-02-21');
        config()->set('services.ghl.agency_token', 'pit-subaccount-token');

        $oauthService = $this->mock(GhlOAuthService::class);
        $oauthService->shouldReceive('getAccessToken')->andReturn('oauth-token-should-not-be-used');

        Http::fake([
            'https://services.leadconnectorhq.com/*' => Http::response([
                'invoice' => ['id' => 'inv-123'],
            ], 201),
        ]);

        $service = app(GhlApiService::class);
        $service->createInvoice('loc-123', [
            'contact_id' => 'contact-1',
            'contact_name' => 'Carlos Test',
            'contact_email' => 'carlos@example.com',
            'contact_phone' => '+18181234567',
            'amount' => 27.77,
            'currency' => 'USD',
            'description' => 'Test invoice',
            'name' => 'Invoice iProcess Test',
        ]);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://services.leadconnectorhq.com/invoices/?altId=loc-123&altType=location'
                && $request->hasHeader('Authorization', 'Bearer pit-subaccount-token')
                && isset($payload['businessDetails']['name'])
                && isset($payload['contactDetails']['id'])
                && is_array($payload['items'] ?? null)
                && ! empty($payload['items'])
                && isset($payload['issueDate']);
        });
    }
}
