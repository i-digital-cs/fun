@php
    $user = auth()->user();
    $locale = app()->getLocale();
    $oppositeLocale = $locale === 'en' ? 'ar' : 'en';
    $initials = user_initials($user?->name);
    $firstName = filled($user?->name) ? explode(' ', trim($user->name))[0] : '';
@endphp

<header class="relative sticky top-0 z-40 flex h-[70px] items-center justify-between border-b border-[#e7ebf3] px-4 sm:px-[1.875rem]">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-white/90 backdrop-blur-xl" aria-hidden="true"></div>
    <div>
        <p class="text-[11px] font-bold uppercase tracking-widest text-indigo-500">{{ __('admin.panel') }}</p>
        <p class="text-sm font-extrabold text-slate-900">@yield('topbar_title', __('admin.dashboard'))</p>
    </div>

    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('locale.update') }}" class="hidden sm:inline-flex">
            @csrf
            <input type="hidden" name="locale" value="{{ $oppositeLocale }}">
            <button type="submit"
                    class="flex h-8 items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 text-[11px] font-bold text-slate-600 transition hover:border-indigo-300 hover:text-indigo-700"
                    aria-label="{{ __('ui.language') }}">
                <span class="font-black text-indigo-500">{{ $locale === 'en' ? 'ع' : 'EN' }}</span>
                {{ $locale === 'en' ? 'AR' : 'EN' }}
            </button>
        </form>

        <details class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-2 marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $initials }}</span>
                <span class="hidden text-xs font-bold text-slate-800 sm:inline">{{ $firstName }}</span>
                <svg class="h-3 w-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 9l6 6 6-6"/>
                </svg>
            </summary>
            <div class="absolute end-0 top-full z-30 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                <a href="{{ route('dashboard') }}" class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('admin.back_to_member') }}
                </a>
                <a href="{{ route('home') }}" class="block whitespace-nowrap px-4 py-2 text-sm font-medium text-slate-700 no-underline hover:bg-slate-50">
                    {{ __('ui.home') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full whitespace-nowrap px-4 py-2 text-start text-sm font-medium text-red-600 hover:bg-red-50">
                        {{ __('ui.logout') }}
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
