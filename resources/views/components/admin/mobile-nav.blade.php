@php
    $links = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => __('admin.dashboard_command')],
        ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => __('admin.users')],
        ['route' => 'admin.events.index', 'match' => 'admin.events.*', 'label' => __('admin.events')],
        ['route' => 'admin.reports.index', 'match' => 'admin.reports.*', 'label' => __('admin.reports')],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 flex h-[65px] items-center justify-around border-t border-[#e7ebf3] bg-white px-2 pt-1.5 max-lg:flex lg:hidden"
     aria-label="{{ __('admin.nav') }}">
    @foreach ($links as $link)
        @php $active = request()->routeIs($link['match']); @endphp
        <a href="{{ route($link['route']) }}"
           @class([
               'flex flex-col items-center gap-0.5 px-1 text-[9px] font-semibold no-underline',
               'font-extrabold text-indigo-600' => $active,
               'text-slate-500' => ! $active,
           ])>
            <span class="truncate">{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>
