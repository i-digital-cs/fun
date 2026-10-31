@php
    $brand = site_display_name();
    $user = auth()->user();

    $groups = [
        [
            'label' => __('admin.nav_group_overview'),
            'links' => [
                ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => __('admin.dashboard_command'), 'icon' => 'home'],
            ],
        ],
        [
            'label' => __('admin.nav_group_users'),
            'links' => [
                ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => __('admin.users'), 'icon' => 'users'],
                ['route' => 'admin.identity.index', 'match' => 'admin.identity.*', 'label' => __('admin.identity_documents'), 'icon' => 'id'],
            ],
        ],
        [
            'label' => __('admin.nav_group_events'),
            'links' => [
                ['route' => 'admin.events.index', 'match' => 'admin.events.index', 'label' => __('admin.events'), 'icon' => 'calendar'],
                ['route' => 'admin.events.calendar', 'match' => 'admin.events.calendar', 'label' => __('admin.events_calendar'), 'icon' => 'calendar'],
            ],
        ],
        [
            'label' => __('admin.nav_group_moderation'),
            'links' => [
                ['route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'label' => __('admin.reports'), 'icon' => 'flag'],
            ],
        ],
        [
            'label' => __('admin.nav_group_catalog'),
            'links' => [
                ['route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'label' => __('catalog.categories'), 'icon' => 'tag'],
                ['route' => 'admin.cities.index', 'match' => 'admin.cities.*', 'label' => __('location.cities'), 'icon' => 'pin'],
            ],
        ],
    ];

    if ($user?->isSuperAdmin()) {
        $groups[] = [
            'label' => __('admin.nav_group_system'),
            'links' => [
                ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'label' => __('admin.site_settings'), 'icon' => 'settings'],
                ['route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'label' => __('admin.audit'), 'icon' => 'audit'],
            ],
        ];
    }
@endphp

<aside class="dash-sidebar relative min-h-screen pb-8">
    <a href="{{ route('admin.dashboard') }}" class="mb-6 flex items-center gap-2.5 px-2.5 pb-1 no-underline">
        @if ($siteLogoUrl ?? site_logo_url())
            <img src="{{ $siteLogoUrl ?? site_logo_url() }}" alt="{{ $brand }}" class="h-10 w-auto max-w-[2.75rem] rounded-[13px] object-contain shadow-lg shadow-[#7138F5]/20">
        @else
            <span class="flex h-10 w-10 items-center justify-center rounded-[13px] bg-white shadow-lg shadow-[#7138F5]/20">
                <x-brand.mark :size="28" />
            </span>
        @endif
        <span class="min-w-0">
            <span class="block truncate text-lg font-extrabold tracking-tight text-[#19213d]">{{ $brand }}</span>
            <span class="block text-[10px] font-bold uppercase tracking-widest text-[#7138F5]">{{ __('admin.panel') }}</span>
        </span>
    </a>

    <nav class="space-y-4" aria-label="{{ __('admin.nav') }}">
        @foreach ($groups as $group)
            <div>
                <p class="mb-1 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $group['label'] }}</p>
                <div class="space-y-0.5">
                    @foreach ($group['links'] as $link)
                        @php $active = request()->routeIs($link['match']); @endphp
                        <a href="{{ route($link['route']) }}"
                           @class([
                               'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
                               'bg-[#7138F5]/10 text-[#7138F5]' => $active,
                               'text-slate-500 hover:bg-[#7138F5]/08 hover:text-[#7138F5]' => ! $active,
                           ])
                           @if ($active) aria-current="page" @endif>
                            @include('admin.partials.nav-icon', ['icon' => $link['icon']])
                            <span class="truncate">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="mt-6 space-y-1 px-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-[11px] px-3 py-2 text-[12px] font-semibold text-slate-500 no-underline hover:bg-slate-50 hover:text-[#7138F5]">
            {{ __('admin.back_to_member') }}
        </a>
        <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-[11px] px-3 py-2 text-[12px] font-semibold text-slate-500 no-underline hover:bg-slate-50 hover:text-[#7138F5]">
            {{ __('ui.home') }}
        </a>
    </div>
</aside>
