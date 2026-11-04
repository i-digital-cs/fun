@php
    $links = [
        ['route' => 'admin.marketing.overview', 'match' => 'admin.marketing.overview', 'label' => __('marketing.nav_overview')],
        ['route' => 'admin.marketing.integrations.index', 'match' => 'admin.marketing.integrations.*', 'label' => __('marketing.nav_integrations')],
        ['route' => 'admin.marketing.budget-policies.index', 'match' => 'admin.marketing.budget-policies.*', 'label' => __('marketing.nav_budget')],
    ];
@endphp

<nav class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-[#E8EAF2] bg-white p-2 text-sm shadow-sm" aria-label="{{ __('marketing.nav_group') }}">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route']) }}"
            @class([
                'rounded-xl px-3.5 py-2 font-semibold no-underline transition',
                'bg-[#7138F5] text-white shadow-md shadow-[#7138F5]/25' => request()->routeIs($link['match']),
                'text-slate-600 hover:bg-[#F1EBFF] hover:text-[#7138F5]' => ! request()->routeIs($link['match']),
            ])
        >
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
