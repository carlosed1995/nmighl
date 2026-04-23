<?php

namespace App\Http\Controllers;

use App\Models\GhlClient;
use App\Models\GhlLocation;
use App\Models\GhlOauthToken;
use App\Models\GhlUserCredential;
use App\Services\GhlApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Throwable;

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
            'location' => ['nullable', 'string', 'max:128'],
            'manual_location' => ['nullable', 'string', 'max:128'],
        ]);

        $token = trim($data['private_integration_token']);

        if (! str_starts_with($token, 'pit-')) {
            throw ValidationException::withMessages([
                'private_integration_token' => 'The token must start with pit-.',
            ]);
        }

        $locationId = trim((string) ($data['location'] ?? ''));
        if ($locationId === '') {
            $locationId = trim((string) ($data['manual_location'] ?? ''));
        }

        if ($locationId === '') {
            throw ValidationException::withMessages([
                'location' => 'Select a sub-account or paste the manual Location ID.',
            ]);
        }

        GhlUserCredential::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'private_integration_token' => $token,
                'default_location_id' => $locationId,
            ]
        );

        return redirect()
            ->route('clients', ['location' => $locationId])
            ->with('status', 'PIT saved for the selected sub-account. You can now sync contacts.');
    }

    public function syncLocations(Request $request, GhlApiService $ghlApiService): RedirectResponse
    {
        try {
            $locations = $ghlApiService->fetchLocations();
        } catch (Throwable $exception) {
            return redirect()
                ->route('clients')
                ->with('error', $exception->getMessage());
        }

        $saved = 0;

        foreach ($locations as $rawLocation) {
            $locationId = (string) ($rawLocation['id'] ?? $rawLocation['_id'] ?? '');

            if ($locationId === '') {
                continue;
            }

            GhlLocation::query()->updateOrCreate(
                ['ghl_id' => $locationId],
                [
                    'name' => (string) ($rawLocation['name'] ?? 'Sin nombre'),
                    'company_id' => $rawLocation['companyId'] ?? null,
                    'timezone' => $rawLocation['timezone'] ?? null,
                    'raw' => $rawLocation,
                ]
            );
            $saved++;
        }

        return redirect()
            ->route('clients')
            ->with('status', "Sub-accounts synced: {$saved}. Now select a sub-account and save your PIT.");
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
                ->with('error', 'First paste and save your Private Integration token.');
        }

        $credential->default_location_id = trim($data['location']);
        $credential->save();

        return redirect()
            ->route('clients', ['location' => $credential->default_location_id])
            ->with('status', 'Sub-account saved for Private Integration.');
    }

    public function sync(Request $request): RedirectResponse
    {
        $selectedLocationId = $request->string('location')->toString();
        $credential = GhlUserCredential::query()->where('user_id', $request->user()->id)->first();
        if (! $credential) {
            return redirect()
                ->route('clients', ['location' => $selectedLocationId !== '' ? $selectedLocationId : null])
                ->with('error', 'First select a sub-account and save your PIT.');
        }

        $effectiveLocation = $selectedLocationId !== ''
            ? $selectedLocationId
            : (string) ($credential->default_location_id ?? '');

        if ($effectiveLocation === '') {
            return redirect()
                ->route('clients')
                ->with('error', 'Select a sub-account and save your PIT before syncing contacts.');
        }

        $parameters = ['--location' => $effectiveLocation];
        $redirectLocation = $effectiveLocation;

        $exitCode = Artisan::call('ghl:sync-clients', $parameters);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()
                ->route('clients', ['location' => $redirectLocation !== '' ? $redirectLocation : null])
                ->with('error', $output !== '' ? $output : 'Failed to sync with GHL.');
        }

        return redirect()
            ->route('clients', ['location' => $redirectLocation !== '' ? $redirectLocation : null])
            ->with('status', $output !== '' ? $output : 'Sync completed. Check the table.');
    }
}
