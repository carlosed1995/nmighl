@extends('layouts.nmi')

@section('title', 'In-Person Payments')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">In-Person Payments</h1>
    <p class="text-slate-500 mb-6">Actividad de terminales, contactless y errores de punto de venta.</p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Terminal Volume</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">$5.1M</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Active Terminals</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">3,450</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <p class="text-sm text-slate-500">Terminal Errors</p>
            <p class="text-3xl font-bold text-emerald-600 mt-2">0.2%</p>
        </div>
    </div>
@endsection
