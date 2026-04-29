@extends('layouts.nmi')

@section('title', ($client->name ?? 'Client') . ' — Invoices')

@section('content')
    <div class="mb-6">
        <a href="{{ route('subaccounts.clients', $location) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <i class="fa-solid fa-arrow-left text-xs"></i>
             Back to {{ $location->name }}
        </a>
        <h1 class="text-3xl font-bold text-slate-900 mb-1">Invoices</h1>
        <p class="text-slate-500">Billing history for {{ $client->name ?? '-' }} · {{ $location->name }}</p>
    </div>

    @php
        $statusStyles = [
            'paid'    => 'bg-emerald-50 text-emerald-700',
            'pending' => 'bg-amber-50 text-amber-700',
            'overdue' => 'bg-rose-50 text-rose-700',
            'draft'   => 'bg-slate-100 text-slate-600',
            'sent'    => 'bg-blue-50 text-blue-700',
        ];
    @endphp

    <x-ui.connect-clients :count="$invoices->total()" label="Total Invoices" />

    <x-ui.data-table :columns="['Invoice #', 'Issued Date', 'Due Date', 'Amount', 'Status', 'Actions']" :paginate="$invoices">
        @forelse ($invoices as $invoice)
            <tr>
                <td class="px-6 py-4 text-slate-900 font-medium font-mono text-xs">{{ $invoice->invoice_number ?? '-' }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $invoice->issued_date?->format('M j, Y') ?? '-' }}</td>
                <td class="px-6 py-4 text-slate-600">{{ $invoice->due_date?->format('M j, Y') ?? '-' }}</td>
                <td class="px-6 py-4 text-slate-900 font-medium">${{ number_format($invoice->amount, 2) }}</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusStyles[$invoice->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="#" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium">
                        <i class="fa-solid fa-eye text-xs"></i>
                        Show
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                    No invoices found for this client.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
@endsection
