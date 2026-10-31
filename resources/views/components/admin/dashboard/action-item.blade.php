@props([
    'item',
])

@php
    $priority = $item['priority'] ?? 'ok';
    $count = (int) ($item['count'] ?? 0);
    $tag = match ($priority) {
        'high' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'medium' => 'bg-amber-50 text-amber-800 ring-amber-100',
        'low' => 'bg-sky-50 text-sky-800 ring-sky-100',
        'info' => 'bg-slate-50 text-slate-600 ring-slate-100',
        default => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    };
    $tagLabel = match ($priority) {
        'high' => __('admin.priority_high'),
        'medium' => __('admin.priority_medium'),
        'low' => __('admin.priority_low'),
        'info' => __('admin.priority_info'),
        default => __('admin.priority_ok'),
    };
    $wrapper = $item['href'] ? 'a' : 'div';
@endphp

<{{ $wrapper }}
    @if ($item['href']) href="{{ $item['href'] }}" @endif
    @class([
        'flex items-start gap-3 rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]',
        'no-underline transition hover:border-[#7138F5]/25 hover:shadow-[0_12px_28px_rgba(113,56,245,0.08)] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20' => (bool) $item['href'],
    ])>
    <div @class([
        'flex shrink-0 items-center justify-center rounded-xl bg-[#F8F9FE] font-extrabold text-[#7138F5]',
        'h-11 w-11 text-lg' => $count < 1000,
        'h-11 min-w-11 px-2 text-sm' => $count >= 1000,
    ])>
        {{ number_format($count) }}
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <p class="text-sm font-bold text-[#19213d]">{{ $item['label'] }}</p>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 ring-inset {{ $tag }}">{{ $tagLabel }}</span>
        </div>
        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $item['description'] }}</p>
    </div>
</{{ $wrapper }}>
