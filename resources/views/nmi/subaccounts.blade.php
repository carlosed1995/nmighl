@extends('layouts.nmi')

@section('title', 'Sub-accounts')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Sub-accounts</h1>
    <p class="text-slate-500 mb-6">Sub-accounts and their associated plans.</p>

    <x-ui.connect-clients :count="$locations->count()" label="Total Sub-accounts" />

    <x-ui.data-table :columns="['Name', 'Plan', 'NMI Connection', 'Actions']">
        @forelse ($locations as $location)
            <tr>
                <td class="px-6 py-4 text-slate-900 font-medium">{{ $location->name }}</td>
                <td class="px-6 py-4 text-slate-500">—</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                        null
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('subaccounts.clients', $location) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium">
                        View Clients
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                    No sub-accounts found. Sync them from Clients-GHL.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
@endsection
