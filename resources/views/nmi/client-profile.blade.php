@extends('layouts.nmi')

@section('title', ($client->name ?? 'Client') . ' — Profile')

@section('content')
    <div class="mb-6">
        <a href="{{ route('subaccounts.clients', $location) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            Back to {{ $location->name }}
        </a>
        <h1 class="text-3xl font-bold text-slate-900 mb-1">{{ $client->name ?? 'No name' }}</h1>
        <p class="text-slate-500">Client profile and contact information.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Email</p>
            <p class="text-sm text-slate-800">{{ $client->email ?? '-' }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Phone</p>
            <p class="text-sm text-slate-800">{{ $client->phone ?? '-' }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Sub-account</p>
            <p class="text-sm text-slate-800">{{ $location->name }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">GHL Contact ID</p>
            <p class="text-sm text-slate-500 font-mono">{{ $client->ghl_contact_id ?? '-' }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Last Activity</p>
            <p class="text-sm text-slate-800">{{ $client->last_activity_at?->format('M j, Y') ?? '-' }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Tags</p>
            <div class="flex flex-wrap gap-1.5">
                @forelse ($client->tags ?? [] as $tag)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700">
                        {{ $tag }}
                    </span>
                @empty
                    <span class="text-sm text-slate-400">No tags</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <a href="{{ route('subaccounts.client.invoices', [$location, $client]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            View Invoices
        </a>
    </div>
@endsection
