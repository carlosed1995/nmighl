<?php

namespace App\Services;

use App\Models\GhlOauthToken;
use App\Models\GhlUserCredential;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GhlOAuthService
{
    public function getAuthorizationUrl(string $state): string
    {
        $clientId = (string) config('services.ghl.client_id');
        $redirectUri = (string) config('services.ghl.redirect_uri');
        $scope = (string) config('services.ghl.scopes');
        $baseUrl = rtrim((string) config('services.ghl.oauth_base_url'), '/');

        if ($clientId === '' || $redirectUri === '') {
            throw new RuntimeException('Falta GHL_CLIENT_ID o GHL_REDIRECT_URI en .env');
        }

        $query = http_build_query([
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'scope' => $scope,
            'state' => $state,
        ]);

        return $baseUrl.'/oauth/chooselocation?'.$query;
    }

    public function exchangeCodeForToken(string $code): GhlOauthToken
    {
        $response = Http::baseUrl(rtrim((string) config('services.ghl.base_url'), '/'))
            ->asForm()
            ->post('/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => (string) config('services.ghl.client_id'),
                'client_secret' => (string) config('services.ghl.client_secret'),
                'redirect_uri' => (string) config('services.ghl.redirect_uri'),
                'code' => $code,
                'user_type' => 'Location',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('No se pudo intercambiar code por token (status '.$response->status().').');
        }

        return $this->storeTokenPayload($response->json());
    }

    public function getAccessToken(): string
    {
        $userPit = GhlUserCredential::query()
            ->where('user_id', Auth::id())
            ->value('private_integration_token');

        if (is_string($userPit) && $userPit !== '') {
            return $userPit;
        }

        if (config('services.ghl.use_private_integration')) {
            $pit = (string) config('services.ghl.agency_token');

            if ($pit === '') {
                throw new RuntimeException('GHL_USE_PRIVATE_INTEGRATION=true pero falta GHL_AGENCY_TOKEN (PIT).');
            }

            return $pit;
        }

        $token = GhlOauthToken::query()->latestValid()->first();

        if (! $token) {
            throw new RuntimeException('No hay token OAuth guardado. Primero conecta la app.');
        }

        if ($token->isExpiringSoon() && $token->refresh_token) {
            $token = $this->refreshToken($token);
        }

        return $token->access_token;
    }

    public function makeAndStoreState(): string
    {
        $state = Str::random(40);
        session(['ghl_oauth_state' => $state]);

        return $state;
    }

    public function validateState(?string $state): bool
    {
        $expected = session('ghl_oauth_state');
        session()->forget('ghl_oauth_state');

        return is_string($state) && is_string($expected) && hash_equals($expected, $state);
    }

    private function refreshToken(GhlOauthToken $token): GhlOauthToken
    {
        $response = Http::baseUrl(rtrim((string) config('services.ghl.base_url'), '/'))
            ->asForm()
            ->post('/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => (string) config('services.ghl.client_id'),
                'client_secret' => (string) config('services.ghl.client_secret'),
                'refresh_token' => $token->refresh_token,
                'user_type' => 'Location',
            ]);

        if (! $response->successful()) {
            return $token;
        }

        return $this->storeTokenPayload($response->json(), $token);
    }

    private function storeTokenPayload(array $payload, ?GhlOauthToken $existing = null): GhlOauthToken
    {
        $locationId = $payload['locationId'] ?? $payload['location_id'] ?? null;
        $companyId = $payload['companyId'] ?? $payload['company_id'] ?? null;
        $expiresIn = (int) ($payload['expires_in'] ?? 0);

        $record = $existing ?? GhlOauthToken::query()->create([
            'provider' => 'ghl',
            'access_token' => '',
        ]);

        $record->fill([
            'provider' => 'ghl',
            'location_id' => $locationId,
            'company_id' => $companyId,
            'access_token' => (string) ($payload['access_token'] ?? ''),
            'refresh_token' => (string) ($payload['refresh_token'] ?? $record->refresh_token),
            'token_type' => (string) ($payload['token_type'] ?? 'Bearer'),
            'scope' => (string) ($payload['scope'] ?? ''),
            'expires_at' => $expiresIn > 0 ? now()->addSeconds($expiresIn) : null,
            'raw' => $payload,
        ]);
        $record->save();

        return $record;
    }
}
