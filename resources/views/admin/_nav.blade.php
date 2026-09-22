@php
    $links = [
        ['route' => 'admin.dashboard', 'label' => __('admin.dashboard')],
        ['route' => 'admin.users.index', 'label' => __('admin.users')],
        ['route' => 'admin.identity.index', 'label' => __('admin.identity_documents')],
        ['route' => 'admin.events.index', 'label' => __('admin.events')],
        ['route' => 'admin.reports.index', 'label' => __('admin.reports')],
        ['route' => 'admin.categories.index', 'label' => __('catalog.categories')],
        ['route' => 'admin.cities.index', 'label' => __('location.cities')],
    ];
@endphp

<nav class="mb-6 flex flex-wrap gap-2 rounded-3xl border border-slate-200/70 bg-white/80 p-3 text-sm shadow-sm backdrop-blur">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route']) }}"
            class="rounded-full px-3.5 py-1.5 font-medium transition {{ request()->routeIs($link['route']) ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}"
        >
            {{ $link['label'] }}
        </a>
    @endforeach
    @if (auth()->user()?->isSuperAdmin())
        <a
            href="{{ route('admin.settings.edit') }}"
            class="rounded-full px-3.5 py-1.5 font-medium transition {{ request()->routeIs('admin.settings.*') ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}"
        >
            {{ __('admin.site_settings') }}
        </a>
        <a
            href="{{ route('admin.audit.index') }}"
            class="rounded-full px-3.5 py-1.5 font-medium transition {{ request()->routeIs('admin.audit.*') ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-700' }}"
        >
            {{ __('admin.audit') }}
        </a>
    @endif
</nav>
