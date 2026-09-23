@if ($nationwideFallback ?? false)
    <p class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ __('events.nationwide_notice') }}</p>
@elseif (! ($hasNearbyOrigin ?? true) && ($events->isNotEmpty() ?? false))
    <p class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ __('events.nationwide_notice') }}</p>
@endif

@if ($events->isEmpty())
    <div class="rounded-3xl border border-slate-200/70 bg-white p-8 text-center shadow-sm">
        @if (($hasNearbyOrigin ?? false) && ($filterCity ?? null))
            <p class="text-slate-500">
                {{ __('events.empty_nearby_city', [
                    'city' => $filterCity->localizedName(),
                    'km' => $filters['distance_km'] ?? config('location.default_distance_km'),
                ]) }}
            </p>
        @else
            <p class="text-slate-500">{{ __('events.empty_nearby') }}</p>
        @endif
        @auth
            @if (auth()->user()->canParticipate())
                <a href="{{ route('events.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                    {{ __('events.create_event') }}
                </a>
            @endif
        @endauth
    </div>
@else
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($events as $event)
            <x-events.card
                :event="$event"
                :join-action="$joinActionsById[$event->id] ?? null"
                :favorited="isset($favoritedIds[$event->id])"
            />
        @endforeach
    </div>
    <div class="mt-6">{{ $events->links() }}</div>
@endif
