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
               'flex min-h-11 min-w-[3.5rem] flex-col items-center justify-center gap-0.5 px-1 text-[10px] font-semibold no-underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[#7138F5]/40',
               'font-extrabold text-[#7138F5]' => $active,
               'text-slate-500' => ! $active,
           ])
           @if ($active) aria-current="page" @endif>
            <span class="truncate max-w-[4.5rem]">{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>
