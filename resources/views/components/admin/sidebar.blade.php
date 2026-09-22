@php
    $brand = site_display_name();
    $user = auth()->user();
    $links = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => __('admin.dashboard'), 'icon' => 'home'],
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => __('admin.users'), 'icon' => 'users'],
        ['route' => 'admin.identity.index', 'match' => 'admin.identity.*', 'label' => __('admin.identity_documents'), 'icon' => 'id'],
        ['route' => 'admin.events.index', 'match' => 'admin.events.*', 'label' => __('admin.events'), 'icon' => 'calendar'],
        ['route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'label' => __('admin.reports'), 'icon' => 'flag'],
        ['route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'label' => __('catalog.categories'), 'icon' => 'tag'],
        ['route' => 'admin.cities.index', 'match' => 'admin.cities.*', 'label' => __('location.cities'), 'icon' => 'pin'],
    ];
    if ($user?->isSuperAdmin()) {
        $links[] = ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'label' => __('admin.site_settings'), 'icon' => 'settings'];
        $links[] = ['route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'label' => __('admin.audit'), 'icon' => 'audit'];
    }
@endphp

<aside class="dash-sidebar relative min-h-screen pb-8">
    <a href="{{ route('admin.dashboard') }}" class="mb-6 flex items-center gap-2.5 px-2.5 pb-1 no-underline">
        <span class="flex h-10 w-10 items-center justify-center rounded-[13px] bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-600/25">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
            </svg>
        </span>
        <span class="min-w-0">
            <span class="block truncate text-lg font-extrabold tracking-tight text-slate-900">{{ $brand }}</span>
            <span class="block text-[10px] font-bold uppercase tracking-widest text-indigo-500">{{ __('admin.panel') }}</span>
        </span>
    </a>

    <nav class="space-y-0.5" aria-label="{{ __('admin.nav') }}">
        @foreach ($links as $link)
            @php $active = request()->routeIs($link['match']); @endphp
            <a href="{{ route($link['route']) }}"
               @class([
                   'flex items-center gap-2.5 rounded-[11px] px-3 py-2.5 text-[13px] font-semibold no-underline transition',
                   'bg-indigo-50 text-indigo-600' => $active,
                   'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' => ! $active,
               ])
               @if ($active) aria-current="page" @endif>
                @include('admin.partials.nav-icon', ['icon' => $link['icon']])
                <span class="truncate">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="mt-6 space-y-1 px-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-[11px] px-3 py-2 text-[12px] font-semibold text-slate-500 no-underline hover:bg-slate-50 hover:text-indigo-600">
            {{ __('admin.back_to_member') }}
        </a>
        <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-[11px] px-3 py-2 text-[12px] font-semibold text-slate-500 no-underline hover:bg-slate-50 hover:text-indigo-600">
            {{ __('ui.home') }}
        </a>
    </div>
</aside>
