@props([
    'kpi',
])

@php
    $delta = $kpi['delta'] ?? null;
    $percent = $kpi['percent'] ?? null;
    $up = is_int($delta) && $delta > 0;
    $down = is_int($delta) && $delta < 0;
    $kind = $kpi['kind'] ?? 'period';
@endphp

<a href="{{ $kpi['href'] }}"
   {{ $attributes->class([
       'group flex flex-col rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_10px_30px_rgba(25,33,61,0.04)] no-underline transition',
       'hover:border-[#7138F5]/30 hover:shadow-[0_16px_40px_rgba(113,56,245,0.10)]',
       'focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20',
   ]) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-[11px] font-bold uppercase tracking-wide text-[#7138F5]/90">{{ $kpi['label'] }}</p>
        <span class="rounded-full bg-[#F8F9FE] px-2 py-0.5 text-[10px] font-bold text-slate-500">
            {{ $kind === 'lifetime' ? __('admin.kpi_kind_lifetime') : __('admin.kpi_kind_period') }}
        </span>
    </div>
    <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#19213d]">{{ number_format((int) $kpi['value']) }}</p>
    @if ($kind === 'period' && $delta !== null)
        <p @class([
            'mt-2 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold',
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
    @else
        <p class="mt-2 text-[11px] font-medium text-slate-400">{{ __('admin.kpi_snapshot') }}</p>
    @endif
    <p class="mt-2 text-[10px] leading-snug text-slate-400">{{ $kpi['hint'] }}</p>
</a>
