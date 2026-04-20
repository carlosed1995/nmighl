<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use App\Models\GhlOauthToken;
use App\Models\GhlUserCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

class ClientsGhlController extends Controller
{
    public function index(Request $request)
    {
        $selectedLocationId = $request->string('location')->toString();

        $hasUserPit = GhlUserCredential::query()->where('user_id', $request->user()->id)->exists();

        $usingPrivateIntegration = ((bool) config('services.ghl.use_private_integration')
            && ((string) config('services.ghl.agency_token')) !== '')
            || $hasUserPit;

        $locations = GhlLocation::orderBy('name')->get();

        $clients = GhlClient::with('location')
            ->when($selectedLocationId !== '', function ($query) use ($selectedLocationId) {
                $query->whereHas('location', function ($locationQuery) use ($selectedLocationId) {
                    $locationQuery->where('ghl_id', $selectedLocationId);
                });
            })
            ->latest()
            ->limit(200)
            ->get();

        return view('nmi.clients-ghl', [
            'locations' => $locations,
            'clients' => $clients,
            'selectedLocationId' => $selectedLocationId,
            'oauthConnected' => $usingPrivateIntegration || GhlOauthToken::query()->latestValid()->exists(),
            'usingPrivateIntegration' => $usingPrivateIntegration,
            'hasUserPit' => $hasUserPit,
        ]);
    }

    public function connectPit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'private_integration_token' => ['required', 'string', 'min:10', 'max:4096'],
        ]);

        $token = trim($data['private_integration_token']);

        if (! str_starts_with($token, 'pit-')) {
            throw ValidationException::withMessages([
                'private_integration_token' => 'El token debe comenzar con pit-.',
            ]);
        }

        GhlUserCredential::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['private_integration_token' => $token]
        );

        return redirect()
            ->route('clients', ['location' => $request->string('location')->toString() ?: null])
            ->with('status', 'Private Integration guardada. Selecciona tu subcuenta y sincroniza.');
    }

    public function saveLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['required', 'string', 'max:128'],
        ]);

        $credential = GhlUserCredential::query()->where('user_id', $request->user()->id)->first();

        if (! $credential) {
            return redirect()
                ->route('clients')
                ->with('error', 'Primero pega y guarda tu Private Integration token.');
        }

        $credential->default_location_id = trim($data['location']);
        $credential->save();

        return redirect()
            ->route('clients', ['location' => $credential->default_location_id])
            ->with('status', 'Subcuenta guardada para Private Integration.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $selectedLocationId = $request->string('location')->toString();
        $parameters = [];

        $credential = GhlUserCredential::query()->where('user_id', $request->user()->id)->first();

        $redirectLocation = $selectedLocationId;

        if ($credential) {
            $effectiveLocation = $selectedLocationId !== ''
                ? $selectedLocationId
                : (string) ($credential->default_location_id ?? '');

            if ($effectiveLocation === '') {
                return redirect()
                    ->route('clients')
                    ->with('error', 'Selecciona una subcuenta antes de sincronizar (Private Integration).');
            }

            $parameters['--location'] = $effectiveLocation;
            $redirectLocation = $effectiveLocation;
        } elseif ($selectedLocationId !== '') {
            $parameters['--location'] = $selectedLocationId;
        }

        $exitCode = Artisan::call('ghl:sync-clients', $parameters);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()
                ->route('clients', ['location' => $redirectLocation !== '' ? $redirectLocation : null])
                ->with('error', $output !== '' ? $output : 'No se pudo sincronizar con GHL.');
        }

        return redirect()
            ->route('clients', ['location' => $redirectLocation !== '' ? $redirectLocation : null])
            ->with('status', $output !== '' ? $output : 'Sincronizacion ejecutada. Revisa la tabla.');
    }
}
