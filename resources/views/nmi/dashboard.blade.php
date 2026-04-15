@extends('layouts.nmi')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Dashboard</h1>
    <p class="text-slate-500 mb-6">Resumen general de comercios, pagos y actividad.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active Merchants</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">1,247</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Transaction Volume</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">$12.4M</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">New Applications</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">38</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Gateway Uptime</p>
            <p class="text-3xl font-bold text-emerald-600 mt-2">99.98%</p>
        </div>
    </div>
@endsection
