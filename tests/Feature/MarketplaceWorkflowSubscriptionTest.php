<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketplaceWorkflowSubscriptionTest extends TestCase
{
    public function test_subscription_endpoint_accepts_valid_payload_with_secret(): void
    {
        config()->set('services.ghl.bridge_webhook_secret', 'test-bridge-secret');

        $response = $this->withHeaders([
            'X-Bridge-Secret' => 'test-bridge-secret',
        ])->postJson('/marketplace/workflows/subscription', [
            'triggerData' => [
                'id' => 'trigger-123',
                'key' => 'usapayments_send_order',
                'eventType' => 'CREATED',
                'targetUrl' => 'https://services.leadconnectorhq.com/workflows-marketplace/triggers/execute/abc/def',
                'filters' => [],
            ],
            'meta' => [
                'key' => 'usapayments_send_order',
                'version' => '1.0',
            ],
            'extras' => [
                'locationId' => 'loc-1',
                'workflowId' => 'wf-1',
                'companyId' => 'comp-1',
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);
    }

    public function test_subscription_endpoint_rejects_request_with_invalid_secret(): void
    {
        config()->set('services.ghl.bridge_webhook_secret', 'test-bridge-secret');

        $response = $this->withHeaders([
            'X-Bridge-Secret' => 'wrong-secret',
        ])->postJson('/marketplace/workflows/subscription', []);

        $response->assertUnauthorized();
    }
}
