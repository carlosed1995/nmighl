<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GhlApiService
{
    public function __construct(private GhlOAuthService $ghlOAuthService)
    {
    }

    public function fetchLocations(): array
    {
        $attempts = [
            fn () => $this->request()->get('/locations/search', ['limit' => 100]),
            fn () => $this->request()->get('/locations', ['limit' => 100]),
            fn () => $this->request()->get('/locations'),
        ];

        foreach ($attempts as $attempt) {
            try {
                $response = $attempt();
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $payload = $response->json();

            $locations = $payload['locations'] ?? $payload['data'] ?? [];

            if (is_array($locations) && count($locations) > 0) {
                return $locations;
            }
        }

        throw new RuntimeException('No se pudieron obtener locations de GHL. Revisa scopes/token.');
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
                'sin acceso a contactos para location '.$locationId.' ('.implode(', ', array_unique($failureReasons)).')'
            );
        }

        return $contacts;
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
            throw new RuntimeException('Falta token GHL. Conecta OAuth o define GHL_AGENCY_TOKEN.');
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
