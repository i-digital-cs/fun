@props([
    'event' => null,
    'src' => null,
    'alt' => '',
    'size' => 'card',
    'priority' => false,
    'lazy' => null,
])

@php
    /** @var 'card'|'detail'|'thumb' $size */
    $attrs = is_object($event) && method_exists($event, 'coverAttrs')
        ? $event->coverAttrs($size)
        : [
            'src' => $src,
            'srcset' => null,
            'sizes' => match ($size) {
                'thumb' => '42px',
                'detail' => '(max-width: 768px) 100vw, 896px',
                default => '(max-width: 640px) 100vw, 360px',
            },
            'width' => match ($size) {
                'thumb' => 84,
                'detail' => 1200,
                default => 640,
            },
            'height' => match ($size) {
                'thumb' => 48,
                'detail' => 675,
                default => 360,
            },
        ];

    $loading = $lazy === null
        ? ($priority ? 'eager' : 'lazy')
        : ($lazy ? 'lazy' : 'eager');
    $fetchpriority = $priority ? 'high' : null;
    $altText = $alt !== ''
        ? $alt
        : (is_object($event) ? (string) ($event->title ?? '') : '');
@endphp

<img
    src="{{ $attrs['src'] }}"
    @if (! empty($attrs['srcset'])) srcset="{{ $attrs['srcset'] }}" @endif
    @if (! empty($attrs['sizes'])) sizes="{{ $attrs['sizes'] }}" @endif
    width="{{ $attrs['width'] }}"
    height="{{ $attrs['height'] }}"
    alt="{{ $altText }}"
    loading="{{ $loading }}"
    decoding="async"
    @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
    {{ $attributes }}
>
