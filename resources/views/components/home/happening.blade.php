@props([
    'events',
    'joinActionsById' => [],
    'favoritedIds' => [],
    'hasNearbyOrigin' => false,
    'nationwideFallback' => false,
    'user' => null,
])

<section class="mb-4 space-y-3" aria-labelledby="home-happening-heading">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 id="home-happening-heading" class="text-xl font-bold tracking-tight text-text sm:text-2xl">
                    @if ($hasNearbyOrigin && $user?->city && ! $nationwideFallback)
                        {{ __('home.happening_near', ['city' => $user->city->localizedName()]) }}
                    @else
                        {{ __('home.happening') }}
                    @endif
                </h2>
                @if ($events->isNotEmpty())
                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-accent" aria-hidden="true"></span>
                @endif
            </div>
            <p class="mt-1 text-sm text-muted">{{ __('home.happening_sub') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-medium text-text hover:border-primary/40">
                <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/>
                </svg>
                {{ __('home.filters') }}
            </a>
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-medium text-text hover:border-primary/40">
                <svg class="h-4 w-4 text-primary" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                </svg>
                {{ __('home.view_map') }}
            </a>
        </div>
    </div>

    @if ($nationwideFallback || (! $hasNearbyOrigin && $events->isNotEmpty()))
        <p class="rounded-lg border border-info/30 bg-info/10 px-4 py-3 text-sm text-info">{{ __('events.nationwide_notice') }}</p>
    @endif

    @if ($events->isEmpty())
        <div class="rounded-2xl border border-border bg-surface px-6 py-10 text-center shadow-sm">
            <p class="text-base font-medium text-text">{{ __('events.empty_nearby') }}</p>
            <p class="mt-2 text-sm text-muted">{{ __('home.empty_hint') }}</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                @auth
                    @if ($user?->canParticipate())
                        <a href="{{ route('events.create') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('home.create_an_event') }}</a>
                    @elseif ($user?->needsIdentityDocuments())
                        <a href="{{ route('identity.create') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('identity.title') }}</a>
                    @endif
                    @unless ($user?->city_id)
                        <a href="{{ route('settings.location.edit') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('events.set_location_cta') }}</a>
                    @endunless
                @else
                    <a href="{{ route('register') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('ui.register') }}</a>
                    <a href="{{ route('events.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('events.discover') }}</a>
                @endauth
            </div>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($events as $event)
                <x-events.card
                    :event="$event"
                    :join-action="$joinActionsById[$event->id] ?? null"
                    :favorited="isset($favoritedIds[$event->id])"
                />
            @endforeach
        </div>
    @endif
</section>
