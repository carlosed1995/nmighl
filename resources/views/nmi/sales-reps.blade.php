@extends('layouts.nmi')

@section('title', 'Sales Reps')

@section('content')
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Sales Representatives & Referrals</h1>
    <p class="text-slate-500 mb-6">Rendimiento de asesores y actividad de referidos.</p>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Advisor</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Referrals</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Monthly Ref. Billing</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-sm">
                <tr><td class="px-6 py-4 font-medium">John Davis</td><td class="px-6 py-4">8 Clients</td><td class="px-6 py-4">$45,200.00</td></tr>
                <tr><td class="px-6 py-4 font-medium">Sarah Miller</td><td class="px-6 py-4">12 Clients</td><td class="px-6 py-4">$128,400.00</td></tr>
            </tbody>
        </table>
    </div>
@endsection
