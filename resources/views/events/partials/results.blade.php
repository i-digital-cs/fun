@if (! empty($nearbyMeta['is_suggestion']))
    @php
        $selectedKm = (int) ($nearbyMeta['selected_radius_km'] ?? 25);
        $effectiveKm = (int) ($nearbyMeta['effective_radius_km'] ?? 0);
        $suggestionText = $effectiveKm === 0
            ? __('events.nearby_suggestions_nationwide', ['selected' => $selectedKm])
            : __('events.nearby_suggestions_banner', [
                'selected' => $selectedKm,
                'effective' => $effectiveKm,
            ]);
    @endphp
    <p class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-[11px] font-medium text-amber-950" data-discover-suggestion-banner>
        {{ $suggestionText }}
    </p>
@elseif ($nationwideFallback ?? false)
    <p class="mb-3 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800">{{ __('events.nationwide_notice') }}</p>
@elseif (! ($hasNearbyOrigin ?? true) && ($events->isNotEmpty() ?? false))
    <p class="mb-3 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-800">{{ __('events.nationwide_notice') }}</p>
@endif

@if ($events->isEmpty())
    <div class="rounded-xl border border-slate-200/70 bg-white p-6 text-center">
        @if (($hasNearbyOrigin ?? false) && ($filterCity ?? null))
            <p class="text-sm text-slate-500">
                {{ __('events.empty_nearby_city', [
                    'city' => $filterCity->localizedName(),
                    'km' => $filters['distance_km'] ?? config('location.default_distance_km'),
                ]) }}
            </p>
        @else
            <p class="text-sm text-slate-500">{{ __('events.empty_nearby') }}</p>
        @endif
        @auth
            @if (auth()->user()->canParticipate())
                <a href="{{ route('events.create') }}" class="mt-3 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2 text-xs font-extrabold text-white shadow-md shadow-orange-500/30">
                    {{ __('events.create_event') }}
                </a>
            @endif
        @endauth
    </div>
@else
    <div class="flex flex-col gap-2">
        @foreach ($events as $event)
            @php
                $hasExact = $event->hasPlacePin();
                $city = $event->relationLoaded('city') ? $event->city : $event->city;
                $cityLat = $city?->latitude;
                $cityLng = $city?->longitude;
                $geo = $hasExact ? 'exact' : (($cityLat !== null && $cityLng !== null) ? 'city' : 'none');
            @endphp
            <div
                data-event-id="{{ $event->id }}"
                data-discover-result-card
                data-geo="{{ $geo }}"
                @if ($hasExact)
                    data-lat="{{ $event->latitude }}"
                    data-lng="{{ $event->longitude }}"
                @endif
                @if ($city)
                    data-city-id="{{ $city->id }}"
                    data-city-name="{{ $city->localizedName() }}"
                    @if ($cityLat !== null && $cityLng !== null)
                        data-city-lat="{{ $cityLat }}"
                        data-city-lng="{{ $cityLng }}"
                    @endif
                @endif
                class="rounded-xl transition"
            >
                <x-events.discover-card :event="$event" />
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
@endif
