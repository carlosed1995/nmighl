@props([
    'columns' => [],
    'paginate' => null,
])

<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                @foreach ($columns as $column)
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white text-sm">
            {{ $slot }}
        </tbody>
    </table>

    @if ($paginate && $paginate->hasPages())
        <div class="px-6 py-3 border-t border-slate-200 bg-slate-50">
            {{ $paginate->links() }}
        </div>
    @endif
</div>
