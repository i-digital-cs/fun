@props([
    'item',
])

@php
    $priority = $item['priority'] ?? 'ok';
    $count = (int) ($item['count'] ?? 0);
    $tier = match ($priority) {
        'high' => 'critical',
        'medium' => 'warning',
        'low', 'info' => 'info',
        default => 'ok',
    };
    $tag = match ($tier) {
        'critical' => 'bg-rose-100 text-rose-800 ring-rose-200',
        'warning' => 'bg-amber-100 text-amber-900 ring-amber-200',
        'info' => 'bg-sky-50 text-sky-800 ring-sky-100',
        default => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
    };
    $tagLabel = match ($tier) {
        'critical' => __('admin.priority_critical'),
        'warning' => __('admin.priority_warning'),
        'info' => __('admin.priority_info'),
        default => __('admin.priority_ok'),
    };
    $surface = match ($tier) {
        'critical' => 'admin-action-critical',
        'warning' => 'admin-action-warning',
        default => 'admin-action-info',
    };
    $wrapper = $item['href'] ? 'a' : 'div';
@endphp

<{{ $wrapper }}
    @if ($item['href']) href="{{ $item['href'] }}" @endif
    @class([
        'flex items-start gap-3 rounded-xl border p-4 shadow-sm',
        $surface,
        'no-underline transition hover:shadow-md focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20' => (bool) $item['href'],
    ])>
    <div @class([
        'flex shrink-0 items-center justify-center rounded-lg bg-white/80 font-extrabold text-[#7138F5] ring-1 ring-[#e8eaf2]',
        'h-12 w-12 text-lg' => $count < 1000,
        'h-12 min-w-12 px-2 text-sm' => $count >= 1000,
    ])>
        {{ number_format($count) }}
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <p class="text-sm font-bold text-[#19213d] sm:text-[15px]">{{ $item['label'] }}</p>
            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset {{ $tag }}">{{ $tagLabel }}</span>
        </div>
        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $item['description'] }}</p>
    </div>
</{{ $wrapper }}>
