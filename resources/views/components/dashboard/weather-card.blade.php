@props([
    'weather' => null,
])

@if ($weather === null)
    <div {{ $attributes->class(['weather-panel', 'weather-panel--empty']) }}>
        <div class="weather-panel__empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25M12 18.75V21M4.22 4.22l1.59 1.59M18.19 18.19l1.59 1.59M3 12h2.25M18.75 12H21M4.22 19.78l1.59-1.59M18.19 5.81l1.59-1.59"/>
                <circle cx="12" cy="12" r="3.75"/>
            </svg>
        </div>
        <p class="weather-panel__empty-title">{{ __('weather.set_location') }}</p>
        <p class="weather-panel__empty-hint">{{ __('weather.set_location_hint') }}</p>
        <a href="{{ route('settings.location.edit') }}" class="weather-panel__empty-cta">{{ __('weather.set_location') }}</a>
    </div>
@else
    @php
        $theme = $weather['theme'] ?? 'sunny';
        $icon = $weather['icon'] ?? 'sun';
    @endphp
    <div
        {{ $attributes->class(['weather-panel', 'weather-panel--'.$theme]) }}
        role="region"
        aria-label="{{ __('weather.'.$weather['condition']) }}"
    >
        <div class="weather-panel__glow" aria-hidden="true"></div>
        <div class="weather-panel__top">
            <div class="weather-panel__condition">
                <span class="weather-panel__icon">
                    <x-dashboard.weather-icon :type="$icon" />
                </span>
                <div>
                    <p class="weather-panel__label">{{ $weather['label'] }}</p>
                    <p class="weather-panel__city">
                        <span class="weather-panel__city-chip">{{ $weather['city']['name'] }}</span>
                    </p>
                </div>
            </div>
            <div class="weather-panel__temp-block">
                <p class="weather-panel__temp">{{ $weather['temperature'] }}°</p>
                <p class="weather-panel__range">{{ $weather['range'] }}</p>
                <p class="weather-panel__when">{{ $weather['local_time'] }} · {{ $weather['local_date'] }}</p>
            </div>
        </div>
        <div class="weather-panel__days">
            @foreach ($weather['forecast'] as $day)
                <div class="weather-panel__day" title="{{ $day['label'] }}">
                    <span class="weather-panel__day-name">{{ $day['day'] }}</span>
                    <span class="weather-panel__day-icon">
                        <x-dashboard.weather-icon :type="$day['icon']" />
                    </span>
                    @if (isset($day['high']))
                        <span class="weather-panel__day-hi">{{ $day['high'] }}°</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
