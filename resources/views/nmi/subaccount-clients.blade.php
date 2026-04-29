@extends('layouts.nmi')

@section('title', $location->name . ' — Clients')

@section('content')
    <div class="mb-6">
        <a href="{{ route('subaccounts') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Back to Sub-accounts
        </a>
        <h1 class="text-3xl font-bold text-slate-900 mb-1">{{ $location->name }}</h1>
        <p class="text-slate-500">Clients associated with this sub-account.</p>
    </div>

    <x-ui.connect-clients :count="$clients->total()" label="Total Clients" />

    <x-ui.data-table :columns="['Name', 'Email', 'Phone', 'Actions']" :paginate="$clients">
        @forelse ($clients as $client)
            <tr>
                <td class="px-6 py-4 text-slate-900 font-medium">{{ $client->name ?? 'No name' }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $client->email ?? '-' }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $client->phone ?? '-' }}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('subaccounts.client', [$location, $client]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium">
                            <i class="fa-solid fa-eye text-xs"></i>
                            View
                        </a>
                        <a href="{{ route('subaccounts.client.invoices', [$location, $client]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium">
                            <i class="fa-solid fa-file-invoice-dollar text-xs"></i>
                            Invoices
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                    No clients found for this sub-account.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
@endsection
