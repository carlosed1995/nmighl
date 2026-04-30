<?php

namespace App\Services;

use App\Models\GhlOauthToken;
use App\Models\GhlUserCredential;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $request = $this->request(null);

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
            $response = $this->request(null)->get('/locations/'.$locationId);
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
                $response = $this->request(null)->post('/contacts/search', [
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
                    $response = $this->request(null)->get('/contacts/', [
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
        $locationId = $this->resolveLocationId($payload);
        $requestPayload = array_filter([
            'amount' => $payload['amount'] ?? null,
            'transactionId' => $payload['transaction_id'] ?? null,
            'note' => $payload['note'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->request(null, $locationId)
            ->post($this->withLocationQuery('/payments/orders/'.$orderId.'/record-payment', $locationId), $requestPayload);

        if (! $response->successful()) {
            Log::warning('GHL order record-payment failed', [
                'order_id' => $orderId,
                'location_id' => $locationId,
                'request_payload' => $requestPayload,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            $detail = (string) ($response->json('message') ?? $response->json('error') ?? '');
            if ($detail === '') {
                $detail = trim((string) $response->body());
            }
            $detail = $detail !== '' ? ' '.$detail : '';

            throw new RuntimeException('Failed to record payment in GHL order '.$orderId.' (status '.$response->status().').'.$detail);
        }
    }

    /**
     * Record a manual payment on a GHL invoice (not a payments/order).
     *
     * @see https://marketplace.gohighlevel.com/docs/ghl/invoices/record-invoice
     */
    public function recordInvoicePayment(string $invoiceId, array $payload = []): void
    {
        $locationId = $this->resolveLocationId($payload);
        $requestPayload = array_filter([
            'altId' => $locationId,
            'altType' => $locationId ? 'location' : null,
            'mode' => $payload['mode'] ?? 'card',
            'amount' => $payload['amount'] ?? null,
            'notes' => $payload['notes'] ?? $payload['note'] ?? null,
            'meta' => array_filter([
                'source' => $payload['source'] ?? 'nmi-bridge',
                'transactionId' => $payload['transaction_id'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        $version = (string) config('services.ghl.invoice_api_version', '2023-02-21');
        $response = $this->request($version, $locationId)
            ->post($this->withLocationQuery('/invoices/'.$invoiceId.'/record-payment', $locationId), $requestPayload);

        if (! $response->successful()) {
            Log::warning('GHL invoice record-payment failed', [
                'invoice_id' => $invoiceId,
                'location_id' => $locationId,
                'request_payload' => $requestPayload,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            $detail = (string) ($response->json('message') ?? $response->json('error') ?? '');
            if ($detail === '') {
                $detail = trim((string) $response->body());
            }
            $detail = $detail !== '' ? ' '.$detail : '';

            throw new RuntimeException('Failed to record payment in GHL invoice '.$invoiceId.' (status '.$response->status().').'.$detail);
        }
    }

    private function request(?string $version, ?string $locationId = null): PendingRequest
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

        $apiVersion = $version ?? (string) config('services.ghl.api_version', '2021-07-28');

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Version' => $apiVersion,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        if (is_string($locationId) && $locationId !== '') {
            $headers['Location-Id'] = $locationId;
        }

        return Http::baseUrl((string) config('services.ghl.base_url'))
            ->withHeaders($headers)
            ->timeout(30);
    }

    private function resolveLocationId(array $payload): ?string
    {
        $candidate = trim((string) ($payload['location_id'] ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }

        $candidate = trim((string) (GhlOauthToken::query()->latest('id')->value('location_id') ?? ''));

        return $candidate !== '' ? $candidate : null;
    }

    private function withLocationQuery(string $path, ?string $locationId): string
    {
        if (! is_string($locationId) || $locationId === '') {
            return $path;
        }

        $separator = str_contains($path, '?') ? '&' : '?';

        return $path.$separator.http_build_query([
            'altId' => $locationId,
            'altType' => 'location',
        ]);
    }
}
