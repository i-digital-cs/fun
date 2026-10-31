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

<aside class="dash-sidebar relative min-h-screen pb-8" aria-label="{{ __('admin.nav') }}">
    <a href="{{ route('admin.dashboard') }}"
       class="mb-5 flex items-center gap-2.5 px-2.5 pb-2 no-underline focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20 rounded-xl">
        @if ($siteLogoUrl ?? site_logo_url())
            <img src="{{ $siteLogoUrl ?? site_logo_url() }}" alt="{{ $brand }}" class="h-10 w-auto max-w-[2.75rem] rounded-xl object-contain shadow-md shadow-[#7138F5]/15">
        @else
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-md shadow-[#7138F5]/15">
                <x-brand.mark :size="28" />
            </span>
        @endif
        <span class="min-w-0">
            <span class="block truncate text-base font-extrabold tracking-tight text-[#19213d]">{{ $brand }}</span>
            <span class="block text-[10px] font-bold uppercase tracking-widest text-[#7138F5]">{{ __('admin.panel') }}</span>
        </span>
    </a>

    <nav class="space-y-5" aria-label="{{ __('admin.nav') }}">
        @foreach ($groups as $group)
            <div>
                <p class="mb-1.5 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $group['label'] }}</p>
                <div class="space-y-0.5">
                    @foreach ($group['links'] as $link)
                        @php $active = request()->routeIs($link['match']); @endphp
                        <a href="{{ route($link['route']) }}"
                           @class([
                               'group flex min-h-10 items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-semibold no-underline transition',
                               'bg-[#7138F5]/10 text-[#7138F5] ring-1 ring-inset ring-[#7138F5]/15' => $active,
                               'text-slate-600 hover:bg-[#7138F5]/08 hover:text-[#7138F5]' => ! $active,
                               'focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20',
                           ])
                           @if ($active) aria-current="page" @endif>
                            <span @class([
                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-md',
                                'bg-white text-[#7138F5]' => $active,
                                'bg-[#F8F9FE] text-slate-500 group-hover:text-[#7138F5]' => ! $active,
                            ])>
                                @include('admin.partials.nav-icon', ['icon' => $link['icon']])
                            </span>
                            <span class="truncate">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="mt-6 space-y-0.5 border-t border-[#e8eaf2] px-1 pt-4">
        <a href="{{ route('dashboard') }}"
           class="flex min-h-9 items-center gap-2 rounded-lg px-3 py-2 text-[12px] font-semibold text-slate-500 no-underline transition hover:bg-[#F8F9FE] hover:text-[#7138F5] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20">
            {{ __('admin.back_to_member') }}
        </a>
        <a href="{{ route('home') }}"
           class="flex min-h-9 items-center gap-2 rounded-lg px-3 py-2 text-[12px] font-semibold text-slate-500 no-underline transition hover:bg-[#F8F9FE] hover:text-[#7138F5] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20">
            {{ __('ui.home') }}
        </a>
    </div>
</aside>
