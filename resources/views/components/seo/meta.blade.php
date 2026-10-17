@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'url' => null,
    'type' => 'website',
    'robots' => 'index,follow',
    'jsonLd' => null,
    'absoluteTitle' => false,
])

@php
    use App\Support\Seo;

    $brand = site_display_name();
    $pageTitle = filled($title) ? trim((string) $title) : null;
    $documentTitle = ($absoluteTitle && $pageTitle)
        ? $pageTitle
        : ($pageTitle ? $pageTitle.' · '.$brand : $brand);
    $metaDescription = Seo::truncateDescription(
        $description ?: __('design.welcome_sub_generic')
    );
    $canonical = Seo::absoluteUrl($url ?: url()->current());
    $ogImage = Seo::absoluteUrl($image ?: (site_logo_url() ?: asset('logo.svg')));
    $locale = app()->getLocale() === 'ar' ? 'ar_EG' : 'en_US';
    $localeAlt = app()->getLocale() === 'ar' ? 'en_US' : 'ar_EG';
@endphp

<title>{{ $documentTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:site_name" content="{{ $brand }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $documentTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:locale:alternate" content="{{ $localeAlt }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $documentTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<link rel="icon" href="{{ Seo::absoluteUrl(site_logo_url() ?: asset('logo.svg')) }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ Seo::absoluteUrl(site_logo_url() ?: asset('logo.svg')) }}">

@if (is_array($jsonLd) && $jsonLd !== [])
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
