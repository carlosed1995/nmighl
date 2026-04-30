<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceWorkflowSubscriptionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->assertBridgeSecret($request);

        $payload = $request->all();
        $triggerData = (array) data_get($payload, 'triggerData', []);
        $meta = (array) data_get($payload, 'meta', []);
        $extras = (array) data_get($payload, 'extras', []);

        Log::info('Marketplace workflow trigger subscription event received.', [
            'event_type' => data_get($triggerData, 'eventType'),
            'trigger_id' => data_get($triggerData, 'id'),
            'trigger_key' => data_get($triggerData, 'key') ?? data_get($meta, 'key'),
            'workflow_id' => data_get($extras, 'workflowId'),
            'location_id' => data_get($extras, 'locationId'),
            'company_id' => data_get($extras, 'companyId'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription received.',
        ]);
    }

    private function assertBridgeSecret(Request $request): void
    {
        $expected = (string) config('services.ghl.bridge_webhook_secret');
        if ($expected === '') {
            return;
        }

        $incoming = (string) $request->header('X-Bridge-Secret', '');
        abort_if($incoming === '' || ! hash_equals($expected, $incoming), 401, 'Invalid bridge webhook secret.');
    }
}
