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

<nav class="mb-6 flex flex-wrap gap-2 rounded-2xl border border-border bg-surface p-3 text-sm">
    @foreach ($links as $link)
        <a
            href="{{ route($link['route']) }}"
            class="rounded-lg px-3 py-1.5 {{ request()->routeIs($link['route']) ? 'bg-primary text-white' : 'text-muted hover:bg-background hover:text-text' }}"
        >
            {{ $link['label'] }}
        </a>
    @endforeach
    @if (auth()->user()?->isSuperAdmin())
        <a
            href="{{ route('admin.settings.edit') }}"
            class="rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.settings.*') ? 'bg-primary text-white' : 'text-muted hover:bg-background hover:text-text' }}"
        >
            {{ __('admin.site_settings') }}
        </a>
        <a
            href="{{ route('admin.audit.index') }}"
            class="rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.audit.*') ? 'bg-primary text-white' : 'text-muted hover:bg-background hover:text-text' }}"
        >
            {{ __('admin.audit') }}
        </a>
    @endif
</nav>
