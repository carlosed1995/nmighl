@extends('layouts.nmi')

@section('title', 'Online Payments')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Online Payments</h1>
    <p class="text-slate-500 mb-6">Create order, charge with NMI, and track status updates from webhook.</p>

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

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">1) Create local order</h2>
            <form method="POST" action="{{ route('online-payments.orders.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="ghl_client_id" class="block text-sm font-medium text-slate-700 mb-1">Client (synced from GHL)</label>
                    <select id="ghl_client_id" name="ghl_client_id" class="w-full rounded-lg border-slate-300 text-sm" required>
                        <option value="">Select a client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name ?? 'No name' }} - {{ $client->email ?? 'no-email' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
                        <input id="amount" type="number" step="0.01" min="0.50" name="amount" class="w-full rounded-lg border-slate-300 text-sm" placeholder="10.00" required />
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                        <input id="currency" name="currency" value="USD" class="w-full rounded-lg border-slate-300 text-sm" />
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <input id="description" name="description" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Invoice from GHL contact" />
                </div>
                <div>
                    <label for="ghl_order_id" class="block text-sm font-medium text-slate-700 mb-1">GHL Order ID (optional bridge)</label>
                    <input id="ghl_order_id" name="ghl_order_id" class="w-full rounded-lg border-slate-300 text-sm" placeholder="If set, sync uses POST /payments/orders/.../record-payment" />
                </div>
                <div>
                    <label for="ghl_invoice_id" class="block text-sm font-medium text-slate-700 mb-1">GHL Invoice ID (optional bridge)</label>
                    <input id="ghl_invoice_id" name="ghl_invoice_id" class="w-full rounded-lg border-slate-300 text-sm" placeholder="If set (and no order), sync uses POST /invoices/.../record-payment" />
                </div>
                <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                    Create order
                </button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">2) Process payment in NMI</h2>
            <form method="POST" action="{{ route('online-payments.charge') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="order_id" class="block text-sm font-medium text-slate-700 mb-1">Order</label>
                    <select id="order_id" name="order_id" class="w-full rounded-lg border-slate-300 text-sm" required>
                        <option value="">Select order</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}">
                                #{{ $order->id }} - ${{ number_format((float) $order->amount, 2) }} - {{ $order->client?->name ?? 'No client' }} - {{ strtoupper($order->status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="customer_vault_id" class="block text-sm font-medium text-slate-700 mb-1">Customer Vault ID (recommended)</label>
                    <input id="customer_vault_id" name="customer_vault_id" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Optional" />
                </div>
                <p class="text-xs text-slate-500">If no vault ID, enter test card:</p>
                <div class="grid grid-cols-3 gap-3">
                    <input name="cc_number" class="rounded-lg border-slate-300 text-sm" placeholder="cc number" />
                    <input name="cc_exp" class="rounded-lg border-slate-300 text-sm" placeholder="MMYY" />
                    <input name="cc_cvv" class="rounded-lg border-slate-300 text-sm" placeholder="CVV" />
                </div>
                <button type="submit" class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">
                    Charge order
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">GHL Order</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">GHL Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">NMI Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Transaction ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-sm">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-6 py-4 text-slate-900">#{{ $order->id }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $order->client?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">${{ number_format((float) $order->amount, 2) }} {{ $order->currency }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                {{ $order->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $order->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ in_array($order->status, ['declined', 'error'], true) ? 'bg-rose-100 text-rose-700' : '' }}
                                {{ in_array($order->status, ['refunded', 'voided'], true) ? 'bg-slate-200 text-slate-700' : '' }}
                            ">
                                {{ strtoupper($order->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $order->ghl_order_id ?? '-' }}
                            @if ($order->synced_to_ghl_at && $order->ghl_order_id)
                                <span class="ml-1 text-emerald-600 text-xs">synced</span>
                            @elseif ($order->ghl_sync_error && $order->ghl_order_id)
                                <span class="ml-1 text-rose-600 text-xs">sync error</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $order->ghl_invoice_id ?? '-' }}
                            @if ($order->synced_to_ghl_at && $order->ghl_invoice_id)
                                <span class="ml-1 text-emerald-600 text-xs">synced</span>
                            @elseif ($order->ghl_sync_error && $order->ghl_invoice_id)
                                <span class="ml-1 text-rose-600 text-xs">sync error</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $order->nmi_invoice_id ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $order->nmi_transaction_id ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $order->response_message ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-slate-500">
                            No orders yet. Create your first order and process payment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
