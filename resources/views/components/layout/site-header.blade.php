{{-- Step 1 home redesign: site chrome matching code.html (Tailwind tokens, DESIGN.md). --}}
@php
    $brand = $siteName ?? site_display_name();
    $locale = app()->getLocale();
    $isHome = request()->routeIs('home');
    $isDiscover = request()->routeIs('events.index');
    $isMine = request()->routeIs('events.mine');
    $user = auth()->user();
    $canCreate = $user?->canParticipate() === true;
@endphp

<header class="sticky top-0 z-50 border-b border-border/70 bg-surface shadow-sm">
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:gap-4">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 no-underline" aria-label="{{ $brand }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-white" aria-hidden="true">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
            </span>
            <span class="text-[1.05rem] font-bold tracking-tight text-text">{{ $brand }}</span>
        </a>

        <nav class="ms-2 hidden items-center lg:flex" aria-label="{{ __('ui.primary_nav') }}">
            <a href="{{ route('home') }}"
               @class([
                   'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                   'border-primary text-primary font-semibold' => $isHome,
                   'border-transparent text-muted hover:text-text' => ! $isHome,
               ])>
                {{ __('ui.home') }}
            </a>
            <a href="{{ route('events.index') }}"
               @class([
                   'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                   'border-primary text-primary font-semibold' => $isDiscover,
                   'border-transparent text-muted hover:text-text' => ! $isDiscover,
               ])>
                {{ __('events.nearby_events') }}
            </a>
            @auth
                <a href="{{ route('events.mine') }}"
                   @class([
                       'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                       'border-primary text-primary font-semibold' => $isMine,
                       'border-transparent text-muted hover:text-text' => ! $isMine,
                   ])>
                    {{ __('events.my_events') }}
                </a>
            @endauth
        </nav>

        <div class="ms-auto flex items-center gap-2 sm:gap-3">
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

                @if ($user->isPlatformAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden text-sm text-muted hover:text-text md:inline">{{ __('admin.dashboard') }}</a>
                @endif

                <details class="relative lg:hidden">
                    <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-lg border border-border bg-surface text-text marker:content-none [&::-webkit-details-marker]:hidden" aria-label="{{ __('ui.menu') }}">
                        <span class="sr-only">{{ __('ui.menu') }}</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </summary>
                    <div class="absolute end-0 z-50 mt-2 w-56 rounded-xl border border-border bg-surface p-2 shadow-lg">
                        <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('ui.home') }}</a>
                        <a href="{{ route('events.index') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('events.nearby_events') }}</a>
                        <a href="{{ route('events.mine') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('events.my_events') }}</a>
                        <a href="{{ route('settings.location.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-background">{{ __('location.location_settings') }}</a>
                        @if ($canCreate)
                            <a href="{{ route('events.create') }}" class="mt-1 block rounded-lg bg-accent px-3 py-2 text-center text-sm font-semibold text-white">{{ __('events.create_event_cta') }}</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-border pt-1">
                            @csrf
                            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-start text-sm text-muted hover:bg-background">{{ __('ui.logout') }}</button>
                        </form>
                    </div>
                </details>

                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                    @csrf
                    <button type="submit" class="text-sm text-muted hover:text-text">{{ __('ui.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="hidden text-sm text-muted hover:text-text sm:inline">{{ __('ui.login') }}</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-accent px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                    {{ __('ui.register') }}
                </a>
            @endauth
        </div>
    </div>
</header>
