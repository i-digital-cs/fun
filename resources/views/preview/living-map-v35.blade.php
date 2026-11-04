@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', 'Living Map V3.5.7 Preview')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    @if (str_starts_with(app()->getLocale(), 'ar'))
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800&display=swap" rel="stylesheet">
    @endif
@endpush

@section('content')
    <div class="lm-v35-preview mx-auto max-w-[1560px] px-1 sm:px-0">
        <div class="lm-v35-preview__glow" aria-hidden="true"></div>

        <div class="lm-v35-banner" role="status">
            Living Map <strong>V3.5.7</strong> — spotlight polish, marker connection, responsive sheet.
            Isolated preview only. Production Home remains on V3.4 until explicit owner approval.
        </div>

        <x-living-map.stage
            mode="preview"
            variant="v35"
            :style-url="$styleUrl"
            :attribution="$attribution"
            :show-banner="false"
            :show-tour-controls="true"
            :show-radius-control="true"
            :latitude="$latitude"
            :longitude="$longitude"
            :distance-km="$distanceKm"
            :discovery-mode="$discoveryMode"
            :location-label="$locationLabel"
            :has-nearby-origin="true"
        />
    </div>
@endsection
