@props([
    'weather' => null,
])

@if ($weather === null)
    <div class="weather-card weather-card--empty">
        <p class="weather-card__empty-title">{{ __('weather.set_location') }}</p>
        <p class="weather-card__empty-hint">{{ __('weather.set_location_hint') }}</p>
        <a href="{{ route('settings.location.edit') }}" class="weather-card__empty-cta">{{ __('weather.set_location') }}</a>
    </div>
@else
    @php
        $theme = $weather['theme'] ?? 'sunny';
        $icon = $weather['icon'] ?? 'sun';
    @endphp
    <div {{ $attributes->class(['weather-card', 'weather-card--'.$theme]) }} role="region" aria-label="{{ __('weather.'.$weather['condition']) }}">
        <section class="weather-card__info">
            <div class="weather-card__bg" aria-hidden="true">
                <div class="weather-card__circle"></div>
                <div class="weather-card__circle"></div>
                <div class="weather-card__circle"></div>
            </div>
            <div class="weather-card__left">
                <div class="weather-card__condition">
                    <span class="weather-card__icon weather-card__icon--lg">
                        <x-dashboard.weather-icon :type="$icon" />
                    </span>
                    <span>{{ $weather['label'] }}</span>
                </div>
                <div class="weather-card__temp">{{ $weather['temperature'] }}°</div>
                <div class="weather-card__range">{{ $weather['range'] }}</div>
            </div>
            <div class="weather-card__right">
                <div>
                    <div class="weather-card__hour">{{ $weather['local_time'] }}</div>
                    <div class="weather-card__date">{{ $weather['local_date'] }}</div>
                </div>
                <div class="weather-card__city">{{ $weather['city']['name'] }}</div>
            </div>
        </section>
        <section class="weather-card__days">
            @foreach ($weather['forecast'] as $day)
                <div class="weather-card__day" title="{{ $day['label'] }}">
                    <span class="weather-card__day-name">{{ $day['day'] }}</span>
                    <span class="weather-card__icon weather-card__icon--sm">
                        <x-dashboard.weather-icon :type="$day['icon']" />
                    </span>
                </div>
            @endforeach
        </section>
    </div>
@endif
