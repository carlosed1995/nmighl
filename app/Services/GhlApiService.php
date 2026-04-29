<?php

namespace App\Services;

use App\Models\GhlUserCredential;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GhlApiService
{
    public function __construct(private GhlOAuthService $ghlOAuthService)
    {
    }

    public function fetchLocations(): array
    {
        $defaultLocationId = GhlUserCredential::query()
            ->where('user_id', Auth::id())
            ->value('default_location_id');

        if (is_string($defaultLocationId) && $defaultLocationId !== '') {
            $location = $this->fetchLocationById($defaultLocationId);

            if (is_array($location) && $location !== []) {
                return [$location];
            }
        }

        $request = $this->request();

        $attempts = [
            ['/locations/search', fn () => $request->get('/locations/search', ['limit' => 100])],
            ['/locations', fn () => $request->get('/locations', ['limit' => 100])],
            ['/locations', fn () => $request->get('/locations')],
            ['/oauth/installedLocations', fn () => $request->get('/oauth/installedLocations')],
        ];
        $failureReasons = [];

        foreach ($attempts as $attempt) {
            try {
                [$endpoint, $call] = $attempt;
                $response = $call();
            } catch (\Throwable $exception) {
                $failureReasons[] = 'error requesting endpoint: '.$exception->getMessage();
                continue;
            }

            if (! $response->successful()) {
                $failureReasons[] = $endpoint.' returned status '.$response->status();
                continue;
            }

            $payload = $response->json();

            $locations = $payload['locations']
                ?? $payload['data']
                ?? $payload['installedLocations']
                ?? [];

            if (is_array($locations) && count($locations) > 0) {
                return $locations;
            }

            // Some OAuth endpoints return a single location object.
            $singleLocation = $payload['location'] ?? null;
            if (is_array($singleLocation) && $singleLocation !== []) {
                return [$singleLocation];
            }
        }

        throw new RuntimeException(
            'Unable to fetch GHL locations. '.implode(' | ', array_unique($failureReasons))
        );
    }

    private function fetchLocationById(string $locationId): array
    {
        try {
            $response = $this->request()->get('/locations/'.$locationId);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();

        $location = $payload['location'] ?? $payload;

        return is_array($location) ? $location : [];
    }

    public function fetchContactsByLocation(string $locationId): array
    {
        $page = 1;
        $limit = 100;
        $contacts = [];
        $firstPageFailed = false;
        $failureReasons = [];

        do {
            try {
                $response = $this->request()->post('/contacts/search', [
                    'locationId' => $locationId,
                    'page' => $page,
                    'pageLimit' => $limit,
                    'query' => '',
                ]);
            } catch (\Throwable) {
                $response = null;
                $failureReasons[] = 'POST /contacts/search exception';
            }

            if (! $response || ! $response->successful()) {
                if ($response) {
                    $failureReasons[] = 'POST /contacts/search status '.$response->status();
                }
                try {
                    $response = $this->request()->get('/contacts/', [
                        'locationId' => $locationId,
                        'limit' => $limit,
                        'page' => $page,
                    ]);
                } catch (\Throwable) {
                    $response = null;
                    $failureReasons[] = 'GET /contacts exception';
                }
            }

            if (! $response || ! $response->successful()) {
                if ($response) {
                    $failureReasons[] = 'GET /contacts status '.$response->status();
                }
                if ($page === 1) {
                    $firstPageFailed = true;
                }
                break;
            }

            $payload = $response->json();
            $chunk = $payload['contacts'] ?? $payload['data'] ?? [];

            if (! is_array($chunk) || count($chunk) === 0) {
                break;
            }

            $contacts = [...$contacts, ...$chunk];
            $page++;
        } while (count($chunk) === $limit && $page <= 50);

        if ($firstPageFailed) {
            throw new RuntimeException(
                'no access to contacts for location '.$locationId.' ('.implode(', ', array_unique($failureReasons)).')'
            );
        }

        return $contacts;
    }

    public function recordOrderPayment(string $orderId, array $payload = []): void
    {
        $requestPayload = array_filter([
            'amount' => $payload['amount'] ?? null,
            'transactionId' => $payload['transaction_id'] ?? null,
            'note' => $payload['note'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->request()->post('/payments/orders/'.$orderId.'/record-payment', $requestPayload);

        if (! $response->successful()) {
            $detail = (string) ($response->json('message') ?? $response->json('error') ?? '');
            if ($detail === '') {
                $detail = trim((string) $response->body());
            }
            $detail = $detail !== '' ? ' '.$detail : '';

            throw new RuntimeException('Failed to record payment in GHL order '.$orderId.' (status '.$response->status().').'.$detail);
        }
    }

    private function request(): PendingRequest
    {
        $token = '';

        try {
            $token = $this->ghlOAuthService->getAccessToken();
        } catch (\Throwable) {
            $token = (string) config('services.ghl.agency_token');
        }

        if ($token === '') {
            throw new RuntimeException('Missing GHL token. Connect OAuth or define GHL_AGENCY_TOKEN.');
        }

        return Http::baseUrl((string) config('services.ghl.base_url'))
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Version' => '2021-07-28',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }
}
