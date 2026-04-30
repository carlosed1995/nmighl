<?php

namespace App\Http\Controllers;

use App\Services\GhlOAuthService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GhlOAuthController extends Controller
{
    public function __construct(private GhlOAuthService $ghlOAuthService)
    {
    }

    public function connect(): RedirectResponse
    {
        $state = $this->ghlOAuthService->makeAndStoreState();
        $url = $this->ghlOAuthService->getAuthorizationUrl($state);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->query('state');
        $code = (string) $request->query('code', '');
        $hasLocalStateContext = $request->session()->has('ghl_oauth_state')
            || $request->session()->has('ghl_oauth_state_nonce');
        $stateWasProvided = is_string($state) && $state !== '';

        // Support both OAuth entry points:
        // 1) App-initiated (/oauth/connect): state must validate.
        // 2) Marketplace install-initiated: callback may not carry local state context.
        if ($stateWasProvided || $hasLocalStateContext) {
            if (! $this->ghlOAuthService->validateState($stateWasProvided ? $state : null)) {
                return redirect()->route('clients')->with('error', 'Invalid OAuth state.');
            }
        } else {
            Log::info('GHL OAuth callback without local state context (marketplace-initiated flow).', [
                'path' => $request->path(),
                'has_code' => $code !== '',
            ]);
        }

        if ($code === '') {
            return redirect()->route('clients')->with('error', 'Authorization code was not received.');
        }

        try {
            $this->ghlOAuthService->exchangeCodeForToken($code);
        } catch (RuntimeException $exception) {
            return redirect()->route('clients')->with('error', $exception->getMessage());
        }

        return redirect()->route('clients')->with('status', 'OAuth connected successfully. You can now sync.');
    }
}
