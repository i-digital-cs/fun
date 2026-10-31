<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
@if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
@endif
@if (! empty($url['changefreq']))
        <changefreq>{{ $url['changefreq'] }}</changefreq>
@endif
@if (! empty($url['priority']))
        <priority>{{ $url['priority'] }}</priority>
@endif
@foreach (($url['alternates'] ?? []) as $hreflang => $href)
        <xhtml:link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}" />
@endforeach
    </url>
@if (! empty($url['alternates']['ar']) && ($url['alternates']['ar'] ?? '') !== ($url['loc'] ?? ''))
    <url>
        <loc>{{ $url['alternates']['ar'] }}</loc>
@if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
@endif
@if (! empty($url['changefreq']))
        <changefreq>{{ $url['changefreq'] }}</changefreq>
@endif
@if (! empty($url['priority']))
        <priority>{{ $url['priority'] }}</priority>
@endif
@foreach (($url['alternates'] ?? []) as $hreflang => $href)
        <xhtml:link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}" />
@endforeach
    </url>
@endif
@endforeach
</urlset>
