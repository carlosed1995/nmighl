<?php

namespace App\Services;

use App\Models\NmiLocationCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NmiSubscriptionService
{
    /**
     * @param  array<int, string>  $events
     */
    public function createSubscription(NmiLocationCredential $credential, string $callbackUrl, array $events): array
    {
        $apiKey = trim((string) $credential->api_security_key);
        $secret = trim((string) $credential->webhook_secret);

        if ($apiKey === '') {
            throw new RuntimeException('Missing NMI API key for this sub-account.');
        }
        if ($secret === '') {
            throw new RuntimeException('Missing webhook secret for this sub-account.');
        }
        if ($callbackUrl === '') {
            throw new RuntimeException('Webhook callback URL is required.');
        }

        $payload = [
            'callback_url' => $callbackUrl,
            'secret' => $secret,
            'events' => array_values(array_filter($events, fn ($event) => is_string($event) && trim($event) !== '')),
        ];

        $response = Http::baseUrl((string) config('services.nmi.subscriptions_api_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'Idempotency-Key' => (string) Str::uuid(),
            ])
            ->post('/subscriptions', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Failed creating NMI webhook subscription (status '.$response->status().'). '.$response->body());
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Unexpected NMI subscription response format.');
        }

        $subscriptionId = (string) ($data['id'] ?? $data['subscription_id'] ?? '');
        if ($subscriptionId === '') {
            throw new RuntimeException('NMI subscription response did not include an id.');
        }

        $credential->subscription_id = $subscriptionId;
        $credential->subscribed_events = $payload['events'];
        $credential->subscription_last_synced_at = now();
        $credential->save();

        return $data;
    }
}
