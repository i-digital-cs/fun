@props([
    'kpi',
    'variant' => 'primary',
])

@php
    $delta = $kpi['delta'] ?? null;
    $percent = $kpi['percent'] ?? null;
    $up = is_int($delta) && $delta > 0;
    $down = is_int($delta) && $delta < 0;
    $kind = $kpi['kind'] ?? 'period';
    $isPrimary = $variant === 'primary';
@endphp

<a href="{{ $kpi['href'] }}"
   {{ $attributes->class([
       'group flex h-full flex-col border border-[#e8eaf2] bg-white no-underline transition',
       'rounded-xl p-4 shadow-[0_8px_24px_rgba(25,33,61,0.04)] admin-kpi-primary' => $isPrimary,
       'rounded-lg px-3 py-3 shadow-sm admin-kpi-secondary' => ! $isPrimary,
       'hover:border-[#7138F5]/35 hover:shadow-[0_12px_28px_rgba(113,56,245,0.08)]',
       'focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20',
   ]) }}>
    <div class="flex items-start justify-between gap-2">
        <p @class([
            'font-bold uppercase tracking-wide text-[#7138F5]',
            'text-[11px] sm:text-xs' => $isPrimary,
            'text-[10px]' => ! $isPrimary,
        ])>{{ $kpi['label'] }}</p>
        <span class="shrink-0 rounded-md bg-[#F8F9FE] px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">
            {{ $kind === 'lifetime' ? __('admin.kpi_kind_lifetime') : __('admin.kpi_kind_period') }}
        </span>
    </div>

    <p @class([
        'mt-2 font-extrabold tracking-tight text-[#19213d]',
        'text-[1.75rem] leading-none sm:text-[2rem]' => $isPrimary,
        'text-xl leading-none' => ! $isPrimary,
    ])>{{ number_format((int) $kpi['value']) }}</p>

    @if ($kind === 'period' && $delta !== null)
        <p @class([
            'mt-2 flex flex-wrap items-center gap-1 text-xs font-semibold',
            'text-emerald-600' => $up,
            'text-rose-600' => $down,
            'text-slate-400' => ! $up && ! $down,
        ])>
            <span>
                @if ($up)+{{ number_format($delta) }}@elseif ($down){{ number_format($delta) }}@else 0 @endif
                @if ($percent !== null)
                    ({{ $percent > 0 ? '+' : '' }}{{ $percent }}%)
                @endif
            </span>
            <span class="font-medium text-slate-400">{{ __('admin.kpi_vs_previous') }}</span>
        </p>
    @elseif ($isPrimary)
        <p class="mt-2 text-xs font-medium text-slate-400">{{ __('admin.kpi_snapshot') }}</p>
    @endif

    @if ($isPrimary)
        <p class="mt-auto pt-2 text-xs leading-snug text-slate-500">{{ $kpi['hint'] }}</p>
    @endif
</a>
