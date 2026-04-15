@extends('layouts.nmi')

@section('title', 'Online Payments')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Online Payments</h1>
    <p class="text-slate-500 mb-6">Rendimiento de transacciones digitales y gateways.</p>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Gateway</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Transactions</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Volume</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-sm">
                <tr><td class="px-6 py-4">NMI Direct</td><td class="px-6 py-4">12,842</td><td class="px-6 py-4">$2,410,220</td><td class="px-6 py-4 text-emerald-600 font-semibold">Healthy</td></tr>
                <tr><td class="px-6 py-4">eCommerce API</td><td class="px-6 py-4">9,115</td><td class="px-6 py-4">$1,902,100</td><td class="px-6 py-4 text-emerald-600 font-semibold">Healthy</td></tr>
            </tbody>
        </table>
    </div>
@endsection
