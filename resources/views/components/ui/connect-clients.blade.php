@props([
    'count' => 0,
    'label' => 'Connected Clients',
])

<div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm mb-4">
    <p class="text-sm text-slate-500 mb-2">{{ $label }}</p>
    <p class="text-3xl font-bold text-slate-900">{{ $count }}</p>
</div>
