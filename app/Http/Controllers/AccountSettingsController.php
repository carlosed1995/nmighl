<?php

namespace App\Http\Controllers;

use App\Models\NmiLocationCredential;
use App\Models\User;
use App\Services\NmiSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AccountSettingsController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $tenantLocationId = trim((string) $user->ghl_location_id);

        $credential = $tenantLocationId !== ''
            ? NmiLocationCredential::query()->where('ghl_location_id', $tenantLocationId)->first()
            : null;

        return view('nmi.account-settings', [
            'tenantLocationId' => $tenantLocationId,
            'credential' => $credential,
            'defaultWebhookEvents' => [
                'transaction.sale.success',
                'transaction.sale.failure',
                'invoice.paid',
                'transaction.refund.success',
                'transaction.void.success',
            ],
        ]);
    }

    public function updateNmiSettings(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantLocationId = trim((string) $user->ghl_location_id);
        if ($tenantLocationId === '') {
            return redirect()->route('account-settings')->with('error', 'Your user does not have a sub-account location assigned.');
        }

        $data = $request->validate([
            'api_security_key' => ['nullable', 'string', 'max:4096'],
            'webhook_signing_key' => ['nullable', 'string', 'max:4096'],
            'webhook_secret' => ['nullable', 'string', 'min:6', 'max:64'],
        ]);

        NmiLocationCredential::query()->updateOrCreate(
            ['ghl_location_id' => $tenantLocationId],
            [
                'api_security_key' => trim((string) ($data['api_security_key'] ?? '')) ?: null,
                'webhook_signing_key' => trim((string) ($data['webhook_signing_key'] ?? '')) ?: null,
                'webhook_secret' => trim((string) ($data['webhook_secret'] ?? '')) ?: null,
            ]
        );

        return redirect()->route('account-settings')->with('status', 'NMI settings saved for this sub-account.');
    }

    public function createNmiSubscription(Request $request, NmiSubscriptionService $subscriptionService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantLocationId = trim((string) $user->ghl_location_id);
        if ($tenantLocationId === '') {
            return redirect()->route('account-settings')->with('error', 'Your user does not have a sub-account location assigned.');
        }

        $credential = NmiLocationCredential::query()->where('ghl_location_id', $tenantLocationId)->first();
        if (! $credential) {
            return redirect()->route('account-settings')->with('error', 'Save NMI keys first before creating subscription.');
        }

        $data = $request->validate([
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'max:255'],
        ]);

        $callbackUrl = route('webhooks.nmi').'?location='.$tenantLocationId;

        try {
            $subscriptionService->createSubscription($credential, $callbackUrl, $data['events']);
        } catch (RuntimeException $exception) {
            return redirect()->route('account-settings')->with('error', $exception->getMessage());
        }

        return redirect()->route('account-settings')->with('status', 'NMI webhook subscription created successfully.');
    }
}
