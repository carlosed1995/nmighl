<?php

namespace App\Http\Controllers;

use App\Services\GhlOAuthService;
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

        if (! $this->ghlOAuthService->validateState(is_string($state) ? $state : null)) {
            return redirect()->route('clients-ghl')->with('error', 'OAuth state invalido.');
        }

        if ($code === '') {
            return redirect()->route('clients-ghl')->with('error', 'No se recibio authorization code.');
        }

        try {
            $this->ghlOAuthService->exchangeCodeForToken($code);
        } catch (RuntimeException $exception) {
            return redirect()->route('clients-ghl')->with('error', $exception->getMessage());
        }

        return redirect()->route('clients-ghl')->with('status', 'OAuth conectado correctamente. Ya puedes sincronizar.');
    }
}
