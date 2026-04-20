@extends('layouts.nmi')

@section('title', 'Clients GHL')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Clients-GHL</h1>
    <p class="text-slate-500 mb-6">Subcuentas y clientes sincronizados desde GoHighLevel.</p>

    <div class="mb-4 rounded-lg border px-4 py-3 {{ $oauthConnected ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
        @if ($oauthConnected)
            Flujo recomendado: 1) sincroniza subcuentas, 2) selecciona subcuenta y guarda PIT, 3) sincroniza contactos.
        @else
            Conecta GHL con OAuth para cargar subcuentas, luego selecciona una y guarda tu PIT (pit-...).
        @endif
    </div>

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

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="POST" action="{{ route('clients.sync-locations') }}">
            @csrf
            <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                Sincronizar subcuentas
            </button>
        </form>

        <form id="location-form" method="GET" action="{{ route('clients') }}" class="flex items-end gap-3">
            <div>
                <label for="location" class="block text-sm font-medium text-slate-700 mb-1">Subcuenta</label>
                <select id="location" name="location" class="rounded-lg border-slate-300 text-sm w-72" required>
                    <option value="">Selecciona una subcuenta</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->ghl_id }}" @selected($selectedLocationId === $location->ghl_id)>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 px-4 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-sm font-medium">
                Filtrar
            </button>
        </form>

        <form method="POST" action="{{ route('clients.sync') }}">
            @csrf
            <input type="hidden" name="location" value="{{ $selectedLocationId }}">
            <button type="submit" class="h-10 px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium">
                Sincronizar contactos
            </button>
        </form>
        @unless (! empty($hasUserPit) || (((bool) config('services.ghl.use_private_integration')) && ((string) config('services.ghl.agency_token')) !== ''))
            <a href="{{ route('oauth.connect') }}" class="h-10 inline-flex items-center px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                Conectar OAuth
            </a>
        @endunless
    </div>

    <div class="mb-6 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900 mb-2">Private Integration (pit-...)</h2>
        <p class="text-sm text-slate-500 mb-4">
            Despues de sincronizar subcuentas y elegir una subcuenta, pega tu token PIT para habilitar la sincronizacion de contactos.
        </p>

        <form id="pit-form" method="POST" action="{{ route('clients.pit') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
            @csrf
            <input type="hidden" id="pit-location" name="location" value="{{ $selectedLocationId }}">
            <div class="flex-1">
                <label for="private_integration_token" class="block text-sm font-medium text-slate-700 mb-1">Token</label>
                <input
                    id="private_integration_token"
                    name="private_integration_token"
                    type="password"
                    autocomplete="off"
                    class="w-full rounded-lg border-slate-300 text-sm"
                    placeholder="pit-..."
                />
            </div>
            <div class="flex-1">
                <label for="manual_location" class="block text-sm font-medium text-slate-700 mb-1">Location ID (manual, opcional)</label>
                <input
                    id="manual_location"
                    name="manual_location"
                    type="text"
                    autocomplete="off"
                    class="w-full rounded-lg border-slate-300 text-sm"
                    placeholder="Ej: ve9EPM428h8vShlRW1KT"
                />
            </div>
            <button id="pit-submit" type="submit" class="h-10 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium inline-flex items-center gap-2">
                <span id="pit-submit-label">{{ ! empty($hasUserPit) ? 'Actualizar PIT' : 'Guardar PIT' }}</span>
                <svg id="pit-spinner" class="hidden h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </button>
        </form>

        @error('private_integration_token')
            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
        @enderror
        @error('location')
            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
        @enderror
        @error('manual_location')
            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div id="page-loading" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-[1px] items-center justify-center">
        <div class="bg-white rounded-xl px-6 py-4 shadow-lg text-sm font-medium text-slate-800">
            Conectando con GHL...
        </div>
    </div>

    <script>
        (function () {
            const pitForm = document.getElementById('pit-form');
            const pitSubmit = document.getElementById('pit-submit');
            const pitSpinner = document.getElementById('pit-spinner');
            const pageLoading = document.getElementById('page-loading');
            const locationSelect = document.getElementById('location');
            const pitLocationInput = document.getElementById('pit-location');
            const manualLocationInput = document.getElementById('manual_location');

            function showLoading(show) {
                if (!pageLoading) return;
                pageLoading.classList.toggle('hidden', !show);
                pageLoading.classList.toggle('flex', show);
            }

            if (pitForm && pitSubmit && pitSpinner) {
                pitForm.addEventListener('submit', function (event) {
                    const selectedLocation = locationSelect ? locationSelect.value : '';
                    const manualLocation = manualLocationInput ? manualLocationInput.value.trim() : '';

                    if (!selectedLocation && !manualLocation) {
                        event.preventDefault();
                        alert('Selecciona una subcuenta o pega el Location ID manual.');
                        return;
                    }

                    if (pitLocationInput) {
                        pitLocationInput.value = selectedLocation;
                    }

                    pitSubmit.setAttribute('disabled', 'disabled');
                    pitSpinner.classList.remove('hidden');
                    showLoading(true);
                });
            }
        })();
    </script>

    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm mb-4">
        <p class="text-sm text-slate-500 mb-2">Connected Clients</p>
        <p class="text-3xl font-bold text-slate-900">{{ $clients->count() }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Subcuenta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-sm">
                @forelse ($clients as $client)
                    <tr>
                        <td class="px-6 py-4 text-slate-900 font-medium">{{ $client->name ?? 'Sin nombre' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $client->email ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $client->phone ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $client->location?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                            No hay clientes sincronizados aun. Usa "Sincronizar contactos".
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
