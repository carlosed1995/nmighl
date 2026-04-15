<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use App\Models\GhlOauthToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ClientsGhlController extends Controller
{
    public function index(Request $request)
    {
        $selectedLocationId = $request->string('location')->toString();

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
            'oauthConnected' => GhlOauthToken::query()->latestValid()->exists(),
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        $selectedLocationId = $request->string('location')->toString();
        $parameters = [];

        if ($selectedLocationId !== '') {
            $parameters['--location'] = $selectedLocationId;
        }

        $exitCode = Artisan::call('ghl:sync-clients', $parameters);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()
                ->route('clients-ghl', ['location' => $selectedLocationId !== '' ? $selectedLocationId : null])
                ->with('error', $output !== '' ? $output : 'No se pudo sincronizar con GHL.');
        }

        return redirect()
            ->route('clients-ghl', ['location' => $selectedLocationId !== '' ? $selectedLocationId : null])
            ->with('status', $output !== '' ? $output : 'Sincronizacion ejecutada. Revisa la tabla.');
    }
}
