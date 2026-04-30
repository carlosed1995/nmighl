<?php

namespace App\Services;

use App\Models\GhlOauthToken;
use App\Models\GhlUserCredential;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GhlOAuthService
{
    private const STATE_TTL_MINUTES = 10;

    public function getAuthorizationUrl(string $state): string
    {
        $clientId = (string) config('services.ghl.client_id');
        $redirectUri = (string) config('services.ghl.redirect_uri');
        $scope = (string) config('services.ghl.scopes');
        $baseUrl = rtrim((string) config('services.ghl.oauth_base_url'), '/');

        if ($clientId === '' || $redirectUri === '') {
            throw new RuntimeException('Missing GHL_CLIENT_ID or GHL_REDIRECT_URI in .env');
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
            throw new RuntimeException('Failed to exchange code for token (status '.$response->status().').');
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
                throw new RuntimeException('GHL_USE_PRIVATE_INTEGRATION=true but GHL_AGENCY_TOKEN (PIT) is missing.');
            }

            return $pit;
        }

        $token = GhlOauthToken::query()->latestValid()->first();

        // Prefer OAuth token whenever available.
        if (! $token) {
            // Fallback to server PIT only when OAuth is not connected yet.
            $serverPit = (string) config('services.ghl.agency_token');
            if ($serverPit !== '') {
                return $serverPit;
            }

            throw new RuntimeException('No OAuth token saved. Connect the app first.');
        }

        if ($token->isExpiringSoon() && $token->refresh_token) {
            $token = $this->refreshToken($token);
        }

        return $token->access_token;
    }

    public function makeAndStoreState(): string
    {
        $nonce = Str::random(40);
        $state = $this->signStatePayload($nonce);
        Cache::put($this->stateCacheKey($nonce), true, now()->addMinutes(self::STATE_TTL_MINUTES));
        session(['ghl_oauth_state' => $state, 'ghl_oauth_state_nonce' => $nonce]);

        return $state;
    }

    public function validateState(?string $state): bool
    {
        if (! is_string($state) || $state === '') {
            session()->forget('ghl_oauth_state');

            return false;
        }

        $parsedNonce = $this->extractNonceFromSignedState($state);
        if ($parsedNonce !== null && Cache::pull($this->stateCacheKey($parsedNonce)) === true) {
            session()->forget(['ghl_oauth_state', 'ghl_oauth_state_nonce']);

            return true;
        }

        if (Cache::pull($this->stateCacheKey($state)) === true) {
            session()->forget(['ghl_oauth_state', 'ghl_oauth_state_nonce']);

            return true;
        }

        $expected = session('ghl_oauth_state');
        session()->forget(['ghl_oauth_state', 'ghl_oauth_state_nonce']);

        return is_string($expected) && hash_equals($expected, $state);
    }

    private function stateCacheKey(string $state): string
    {
        return 'ghl_oauth_state:'.hash('sha256', $state);
    }

    private function signStatePayload(string $nonce): string
    {
        $payload = $this->base64UrlEncode(json_encode([
            'nonce' => $nonce,
            'issued_at' => now()->timestamp,
        ], JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $payload, $this->stateSigningKey());

        return $payload.'.'.$signature;
    }

    private function extractNonceFromSignedState(string $state): ?string
    {
        $parts = explode('.', $state, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $payload, $this->stateSigningKey());
        if (! hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = json_decode($this->base64UrlDecode($payload), true);
        if (! is_array($decoded)) {
            return null;
        }

        $nonce = $decoded['nonce'] ?? null;
        $issuedAt = $decoded['issued_at'] ?? null;
        if (! is_string($nonce) || $nonce === '' || ! is_numeric($issuedAt)) {
            return null;
        }

        if ((int) $issuedAt < now()->subMinutes(self::STATE_TTL_MINUTES)->timestamp) {
            return null;
        }

        return $nonce;
    }

    private function stateSigningKey(): string
    {
        return (string) config('app.key');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
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
