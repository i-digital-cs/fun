@props([
    'running' => null,
    'next' => null,
    'previous' => null,
    'brand' => null,
    'logoUrl' => null,
])

@php
    $brand = $brand ?? site_display_name();
    $logoUrl = $logoUrl ?? site_logo_url();
    $next = collect($next ?? []);
    $previous = collect($previous ?? []);
    $hasAny = $running || $next->isNotEmpty() || $previous->isNotEmpty();

    $timeLabel = function ($event) {
        $starts = $event->starts_at?->timezone(config('app.timezone'));
        if (! $starts) {
            return '';
        }
        if ($starts->isToday()) {
            return __('events.today').' · '.$starts->translatedFormat('g:i A');
        }
        if ($starts->isTomorrow()) {
            return __('events.tomorrow').' · '.$starts->translatedFormat('g:i A');
        }

        return $starts->translatedFormat('D · M j · g:i A');
    };
@endphp

@if ($hasAny)
    <div {{ $attributes->class(['now-playing']) }}>
        <div class="now-playing__header">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="" class="now-playing__brand-mark">
            @else
                <span class="now-playing__brand-fallback" aria-hidden="true">{{ mb_substr($brand, 0, 1) }}</span>
            @endif
            <p class="now-playing__heading">{{ __('dashboard.now_playing_title', ['brand' => $brand]) }}</p>
        </div>

        @if ($running)
            <a href="{{ route('events.show', $running) }}" class="now-playing__row now-playing__row--live">
                <div class="now-playing__meta">
                    <p class="now-playing__name">{{ $running->title }}</p>
                    <p class="now-playing__artist">
                        {{ $running->category?->localizedName() ?? __('events.events') }}
                        @if ($running->city) · {{ $running->city->localizedName() }} @endif
                    </p>
                </div>
                <img
                    src="{{ event_cover_url($running->imageUrl(), $running->id) }}"
                    alt=""
                    class="now-playing__cover"
                >
                <div class="now-playing__eq" aria-hidden="true">
                    <span class="now-playing__bar"></span>
                    <span class="now-playing__bar"></span>
                    <span class="now-playing__bar"></span>
                    <span class="now-playing__bar"></span>
                </div>
            </a>
            <p class="now-playing__live-label">{{ __('dashboard.now_playing_live') }}</p>
        @else
            <p class="now-playing__idle">{{ __('dashboard.now_playing_idle') }}</p>
        @endif

        @foreach ($next as $event)
            <a href="{{ route('events.show', $event) }}" class="now-playing__row">
                <div class="now-playing__meta">
                    <p class="now-playing__name">{{ $event->title }}</p>
                    <p class="now-playing__artist">{{ __('dashboard.now_playing_up_next') }} · {{ $timeLabel($event) }}</p>
                </div>
                <img
                    src="{{ event_cover_url($event->imageUrl(), $event->id) }}"
                    alt=""
                    class="now-playing__cover"
                >
                <span class="now-playing__play" aria-hidden="true"></span>
            </a>
        @endforeach

        @foreach ($previous as $event)
            <a href="{{ route('events.show', $event) }}" class="now-playing__row now-playing__row--past">
                <div class="now-playing__meta">
                    <p class="now-playing__name">{{ $event->title }}</p>
                    <p class="now-playing__artist">{{ __('dashboard.now_playing_previous') }} · {{ $timeLabel($event) }}</p>
                </div>
                <img
                    src="{{ event_cover_url($event->imageUrl(), $event->id) }}"
                    alt=""
                    class="now-playing__cover"
                >
                <span class="now-playing__play" aria-hidden="true"></span>
            </a>
        @endforeach
    </div>
@endif
