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
            <div class="now-playing__brand">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="" class="now-playing__brand-mark">
                @else
                    <span class="now-playing__brand-fallback" aria-hidden="true">{{ mb_substr($brand, 0, 1) }}</span>
                @endif
                <div>
                    <p class="now-playing__title">{{ __('dashboard.now_playing_title', ['brand' => $brand]) }}</p>
                    <p class="now-playing__subtitle">
                        @if ($running)
                            <span class="now-playing__live-dot" aria-hidden="true"></span>
                            {{ __('dashboard.now_playing_live') }}
                        @else
                            {{ __('dashboard.now_playing_idle') }}
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('events.index') }}" class="now-playing__browse">{{ __('events.discover') }}</a>
        </div>

        <div class="now-playing__list">
            @if ($running)
                <a href="{{ event_show_url($running) }}" class="now-playing__row now-playing__row--live">
                    <x-events.cover-img
                        :event="$running"
                        size="thumb"
                        :alt="$running->title"
                        class="now-playing__cover"
                    />
                    <span class="now-playing__meta">
                        <span class="now-playing__name bidi-text" dir="{{ bidi_dir($running->title) }}">{{ $running->title }}</span>
                        <span class="now-playing__desc">
                            {{ $running->category?->localizedName() ?? __('events.events') }}
                            @if ($running->city) · {{ $running->city->localizedName() }} @endif
                        </span>
                    </span>
                    <span class="now-playing__eq" aria-hidden="true">
                        <span class="now-playing__bar"></span>
                        <span class="now-playing__bar"></span>
                        <span class="now-playing__bar"></span>
                        <span class="now-playing__bar"></span>
                    </span>
                </a>
            @endif

            @foreach ($next as $event)
                <a href="{{ event_show_url($event) }}" class="now-playing__row">
                    <x-events.cover-img
                        :event="$event"
                        size="thumb"
                        :alt="$event->title"
                        class="now-playing__cover"
                    />
                    <span class="now-playing__meta">
                        <span class="now-playing__name bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</span>
                        <span class="now-playing__desc">{{ __('dashboard.now_playing_up_next') }} · {{ $timeLabel($event) }}</span>
                    </span>
                    <span class="now-playing__chevron" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="rtl:rotate-180">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                        </svg>
                    </span>
                </a>
            @endforeach

            @foreach ($previous as $event)
                <a href="{{ event_show_url($event) }}" class="now-playing__row now-playing__row--past">
                    <x-events.cover-img
                        :event="$event"
                        size="thumb"
                        :alt="$event->title"
                        class="now-playing__cover"
                    />
                    <span class="now-playing__meta">
                        <span class="now-playing__name bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</span>
                        <span class="now-playing__desc">{{ __('dashboard.now_playing_previous') }} · {{ $timeLabel($event) }}</span>
                    </span>
                    <span class="now-playing__chevron" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="rtl:rotate-180">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                        </svg>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
