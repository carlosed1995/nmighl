@extends('layouts.nmi')

@section('title', 'Account Settings')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Account Settings</h1>
    <p class="text-slate-500 mb-6">Configure NMI credentials and webhook subscription for this sub-account.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700 whitespace-pre-line">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Sub-account context</h2>
            <p class="text-sm text-slate-600 mb-3">Location ID: <span class="font-mono">{{ $tenantLocationId !== '' ? $tenantLocationId : 'Not assigned' }}</span></p>
            <p class="text-xs text-slate-500">
                This account stores NMI keys per location, allowing isolated processing for each sub-account.
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Saved webhook status</h2>
            <p class="text-sm text-slate-600">Subscription ID: <span class="font-mono">{{ $credential?->subscription_id ?? '-' }}</span></p>
            <p class="text-sm text-slate-600 mt-1">Last sync: {{ $credential?->subscription_last_synced_at?->toDateTimeString() ?? '-' }}</p>
            <p class="text-sm text-slate-600 mt-1">Events: {{ $credential?->subscribed_events ? implode(', ', $credential->subscribed_events) : '-' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mt-5">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">NMI credentials (per sub-account)</h2>
            <form method="POST" action="{{ route('account-settings.nmi.update') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="api_security_key" class="block text-sm font-medium text-slate-700 mb-1">NMI API key / security key</label>
                    <input id="api_security_key" name="api_security_key" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Paste the API key for this location" />
                </div>
                <div>
                    <label for="webhook_signing_key" class="block text-sm font-medium text-slate-700 mb-1">NMI webhook signing key</label>
                    <input id="webhook_signing_key" name="webhook_signing_key" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Signing key from NMI webhook panel" />
                </div>
                <div>
                    <label for="webhook_secret" class="block text-sm font-medium text-slate-700 mb-1">NMI subscription secret</label>
                    <input id="webhook_secret" name="webhook_secret" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Secret used in Create Subscription API" />
                </div>
                <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                    Save NMI settings
                </button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Create NMI webhook subscription</h2>
            <p class="text-xs text-slate-500 mb-3">This creates a subscription in NMI using your stored API key and secret.</p>
            <form method="POST" action="{{ route('account-settings.nmi.subscribe') }}" class="space-y-3">
                @csrf
                @foreach ($defaultWebhookEvents as $event)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="events[]" value="{{ $event }}" checked class="rounded border-slate-300 text-indigo-600" />
                        <span class="font-mono text-xs">{{ $event }}</span>
                    </label>
                @endforeach
                <button type="submit" class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
                    Create subscription
                </button>
            </form>
        </div>
    </div>
@endsection
