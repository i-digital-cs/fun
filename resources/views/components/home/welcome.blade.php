@props([
    'appName',
    'user' => null,
])

@php
    $isAuth = $user !== null;
    $cityName = $user?->city?->localizedName();
    $isActive = $user?->canParticipate() === true;
    $needsDocs = $user?->needsIdentityDocuments() === true;
@endphp

<section class="mb-8 space-y-4">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                @if ($isAuth)
                    <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">
                        {{ __('home.welcome_back', ['name' => $user->name]) }}
                    </h1>
                    @if ($isActive)
                        <span class="inline-flex items-center gap-1 rounded-full border border-border bg-surface px-2.5 py-1 text-xs font-medium text-success shadow-sm">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-success" aria-hidden="true"></span>
                            @if ($cityName)
                                {{ __('home.active_in_city', ['city' => $cityName]) }}
                            @else
                                {{ __('home.active_account') }}
                            @endif
                        </span>
                    @elseif ($needsDocs)
                        <span class="inline-flex items-center gap-1 rounded-full border border-warning/30 bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                            {{ __('home.pending_documents') }}
                        </span>
                    @endif
                @else
                    <p class="text-sm font-medium uppercase tracking-wide text-primary">{{ $appName }}</p>
                @endif
            </div>

            @if ($isAuth)
                <p class="max-w-2xl text-sm text-muted sm:text-base">{{ __('home.welcome_sub') }}</p>
            @else
                <h1 class="max-w-2xl text-3xl font-semibold tracking-tight text-text sm:text-4xl">
                    {{ __('home.headline') }}
                </h1>
                <p class="max-w-xl text-base text-muted sm:text-lg">{{ __('home.subheadline') }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-1.5 self-start rounded-xl bg-slate-100 p-1 shadow-sm md:self-auto" role="group" aria-label="{{ __('home.date_filter') }}">
            <a href="{{ route('events.index', ['when' => 'today']) }}"
               class="rounded-lg bg-surface px-3 py-1.5 text-xs font-semibold text-primary shadow-sm sm:text-sm">
                {{ __('home.today') }}
            </a>
            <a href="{{ route('events.index', ['when' => 'tomorrow']) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-medium text-muted hover:text-text sm:text-sm">
                {{ __('home.tomorrow') }}
            </a>
            <a href="{{ route('events.index', ['when' => 'weekend']) }}"
               class="rounded-lg px-3 py-1.5 text-xs font-medium text-muted hover:text-text sm:text-sm">
                {{ __('home.weekend') }}
            </a>
            <a href="{{ route('events.index') }}"
               class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-muted hover:text-text sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                    <path d="M3 10h18M8 3v4M16 3v4"/>
                </svg>
                {{ __('home.pick_date') }}
            </a>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @guest
            <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                {{ __('ui.register') }}
            </a>
            <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-medium text-text hover:border-primary/40">
                {{ __('ui.login') }}
            </a>
        @else
            @if ($needsDocs)
                <a href="{{ route('identity.create') }}" class="inline-flex items-center rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                    {{ __('identity.title') }}
                </a>
            @elseif ($isActive)
                <a href="{{ route('events.index') }}" class="inline-flex items-center rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                    {{ __('events.discover') }}
                </a>
                @if ($user->city_id === null)
                    <a href="{{ route('settings.location.edit') }}" class="inline-flex items-center rounded-lg border border-border bg-surface px-4 py-2 text-sm font-medium text-text">
                        {{ __('location.location_settings') }}
                    </a>
                @endif
            @endif
        @endguest
    </div>

    @guest
        <p class="max-w-lg text-sm text-muted">{{ __('identity.help') }}</p>
    @endguest
</section>
