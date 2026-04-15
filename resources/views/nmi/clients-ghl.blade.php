@extends('layouts.nmi')

@section('title', 'Clients GHL')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Clients-GHL</h1>
    <p class="text-slate-500 mb-6">Subcuentas y clientes sincronizados desde GoHighLevel.</p>

    <div class="mb-4 rounded-lg border px-4 py-3 {{ $oauthConnected ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
        @if ($oauthConnected)
            OAuth conectado. Ya puedes sincronizar clientes con permisos de la app.
        @else
            OAuth no conectado. Primero haz clic en "Conectar OAuth" para autorizar la app.
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
        <form method="GET" action="{{ route('clients-ghl') }}" class="flex items-end gap-3">
            <div>
                <label for="location" class="block text-sm font-medium text-slate-700 mb-1">Subcuenta</label>
                <select id="location" name="location" class="rounded-lg border-slate-300 text-sm w-72">
                    <option value="">Todas</option>
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

        <form method="POST" action="{{ route('clients-ghl.sync') }}">
            @csrf
            <input type="hidden" name="location" value="{{ $selectedLocationId }}">
            <button type="submit" class="h-10 px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium">
                Sync GHL Ahora{{ $selectedLocationId !== '' ? ' (Subcuenta seleccionada)' : '' }}
            </button>
        </form>
        <a href="{{ route('oauth.connect') }}" class="h-10 inline-flex items-center px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
            Conectar OAuth
        </a>
    </div>

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
                            No hay clientes sincronizados aun. Usa "Sync GHL Ahora".
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
