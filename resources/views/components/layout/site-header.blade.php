{{-- Site chrome aligned to code.html (Tailwind tokens, DESIGN.md). --}}
@php
    $brand = $siteName ?? site_display_name();
    $locale = app()->getLocale();
    $isHome = request()->routeIs('home');
    $isDiscover = request()->routeIs('events.index');
    $isMine = request()->routeIs('events.mine');
    $user = auth()->user();
    $canCreate = $user?->canParticipate() === true;
    $cityLabel = $user?->city?->localizedName();
    $profileSrc = $user?->profile_image_path;
    $initials = $user
        ? collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : '';
@endphp

<header class="sticky top-0 z-50 border-b border-border/70 bg-surface shadow-sm">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-3 sm:px-6 lg:flex-nowrap lg:gap-3">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 whitespace-nowrap no-underline" aria-label="{{ $brand }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-white" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
            </span>
            <span class="text-[1.05rem] font-bold tracking-tight text-text">{{ $brand }}</span>
        </a>

        <details class="relative ms-auto lg:hidden">
            <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg border border-border bg-surface text-text marker:content-none [&::-webkit-details-marker]:hidden" aria-label="{{ __('ui.menu') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </summary>
            <div class="absolute end-0 z-50 mt-2 w-64 rounded-xl border border-border bg-surface p-2 shadow-lg">
                <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('ui.home') }}</a>
                <a href="{{ route('events.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('events.nearby_events') }}</a>
                @auth
                    <a href="{{ route('events.mine') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('events.my_events') }}</a>
                    <a href="{{ route('events.mine') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('ui.chat') }}</a>
                    <a href="{{ route('settings.location.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('location.location_settings') }}</a>
                    @if ($canCreate)
                        <a href="{{ route('events.create') }}" class="mt-1 block rounded-lg bg-accent px-3 py-2 text-center text-sm font-semibold text-white">{{ __('events.create_event_cta') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-border pt-1">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-start text-sm text-muted hover:bg-background">{{ __('ui.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('ui.login') }}</a>
                    <a href="{{ route('register') }}" class="mt-1 block rounded-lg bg-accent px-3 py-2 text-center text-sm font-semibold text-white">{{ __('ui.register') }}</a>
                @endauth
            </div>
        </details>

        <nav class="order-last hidden w-full items-center gap-0.5 lg:order-none lg:flex lg:w-auto" aria-label="{{ __('ui.primary_nav') }}">
            <a href="{{ route('home') }}"
               @class([
                   'whitespace-nowrap border-b-2 px-3 py-2 text-[0.9375rem] font-medium transition-colors',
                   'border-primary font-semibold text-primary' => $isHome,
                   'border-transparent text-muted hover:text-text' => ! $isHome,
               ])>
                {{ __('ui.home') }}
            </a>
            <a href="{{ route('events.index') }}"
               @class([
                   'whitespace-nowrap border-b-2 px-3 py-2 text-[0.9375rem] font-medium transition-colors',
                   'border-primary font-semibold text-primary' => $isDiscover,
                   'border-transparent text-muted hover:text-text' => ! $isDiscover,
               ])>
                {{ __('events.nearby_events') }}
            </a>
            @auth
                <a href="{{ route('events.mine') }}"
                   @class([
                       'whitespace-nowrap border-b-2 px-3 py-2 text-[0.9375rem] font-medium transition-colors',
                       'border-primary font-semibold text-primary' => $isMine,
                       'border-transparent text-muted hover:text-text' => ! $isMine,
                   ])>
                    {{ __('events.my_events') }}
                </a>
                <a href="{{ route('events.mine') }}" class="inline-flex items-center gap-1.5 whitespace-nowrap border-b-2 border-transparent px-3 py-2 text-[0.9375rem] font-medium text-muted hover:text-text">
                    {{ __('ui.chat') }}
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-accent" aria-hidden="true"></span>
                </a>
            @endauth
        </nav>

        <form method="GET" action="{{ route('events.index') }}" class="mx-0 hidden min-w-[12rem] max-w-[22rem] flex-1 items-center gap-2 rounded-[0.625rem] bg-slate-100 px-2.5 py-1.5 xl:mx-3 xl:flex" role="search">
            <a href="{{ auth()->check() ? route('settings.location.edit') : route('login') }}"
               class="inline-flex shrink-0 items-center gap-1 rounded-full bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary-ink no-underline">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                </svg>
                <span class="max-w-[7rem] truncate">{{ $cityLabel ?? __('home.location_default') }}</span>
            </a>
            <span class="hidden h-4 w-px shrink-0 bg-border sm:block" aria-hidden="true"></span>
            <label class="flex min-w-0 flex-1 items-center gap-1">
                <svg class="h-4 w-4 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" d="M20 20l-3-3"/>
                </svg>
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('home.search_placeholder') }}"
                       aria-label="{{ __('events.search') }}"
                       class="min-w-0 flex-1 border-0 bg-transparent text-sm text-text shadow-none outline-none placeholder:text-muted">
            </label>
        </form>

        <div class="ms-0 flex shrink-0 items-center gap-2 lg:ms-auto">
            <form method="POST" action="{{ route('locale.update') }}" class="inline-flex overflow-hidden rounded-lg border border-border text-sm" role="group" aria-label="{{ __('ui.language') }}">
                @csrf
                <button type="submit" name="locale" value="en"
                        @class([
                            'px-2.5 py-1.5 font-medium transition-colors',
                            'bg-primary text-white' => $locale === 'en',
                            'bg-surface text-muted hover:text-text' => $locale !== 'en',
                        ])
                        aria-pressed="{{ $locale === 'en' ? 'true' : 'false' }}">
                    EN
                </button>
                <button type="submit" name="locale" value="ar"
                        @class([
                            'px-2.5 py-1.5 font-medium transition-colors',
                            'bg-primary text-white' => $locale === 'ar',
                            'bg-surface text-muted hover:text-text' => $locale !== 'ar',
                        ])
                        aria-pressed="{{ $locale === 'ar' ? 'true' : 'false' }}">
                    ع
                </button>
            </form>

            @auth
                @if ($canCreate)
                    <a href="{{ route('events.create') }}" class="hidden rounded-lg bg-accent px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:brightness-95 sm:inline-flex">
                        {{ __('events.create_event_cta') }}
                    </a>
                @elseif ($user->needsIdentityDocuments())
                    <a href="{{ route('identity.create') }}" class="hidden rounded-lg bg-accent px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:brightness-95 sm:inline-flex">
                        {{ __('identity.title') }}
                    </a>
                @endif

                @if ($canCreate)
                    <span class="hidden text-success sm:inline-flex" title="{{ __('home.active_account') }}" aria-label="{{ __('home.active_account') }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1.06 14.54l-3.48-3.48 1.41-1.41 2.07 2.07 4.24-4.24 1.41 1.41-5.65 5.65z"/>
                        </svg>
                    </span>
                @endif

                <a href="{{ route('events.mine') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border bg-background text-muted hover:text-text" aria-label="{{ __('ui.notifications') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .53-.21 1.04-.59 1.41L4 17h5"/>
                        <path stroke-linecap="round" d="M9.5 17a2.5 2.5 0 005 0"/>
                    </svg>
                </a>

                <a href="{{ route('settings.location.edit') }}" class="relative hidden h-8 w-8 shrink-0 sm:inline-flex" aria-label="{{ $user->name }}">
                    @if ($profileSrc)
                        <img src="{{ $profileSrc }}" alt="" width="32" height="32" class="h-8 w-8 rounded-full object-cover">
                    @else
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">{{ $initials }}</span>
                    @endif
                    @if ($canCreate)
                        <span class="absolute -bottom-0.5 -end-0.5 inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-surface text-success" aria-hidden="true">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-4-4 1.41-1.41L11 14.17l5.59-5.59L18 10l-7 7z"/></svg>
                        </span>
                    @endif
                </a>

                @if ($user->isPlatformAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden text-sm text-muted hover:text-text xl:inline">{{ __('admin.dashboard') }}</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="hidden text-sm text-muted hover:text-text sm:inline">{{ __('ui.login') }}</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-accent px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                    {{ __('ui.register') }}
                </a>
            @endauth
        </div>
    </div>
</header>
