@props([
    'size' => 40,
    'class' => '',
])

{{-- Official Funity mark (Brand Identity System) — interlocking rings + locality pin. --}}
<svg
    {{ $attributes->class([$class]) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 100 100"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-hidden="{{ $attributes->get('aria-label') ? 'false' : 'true' }}"
>
    <circle cx="38" cy="50" r="22" stroke="#4F46E5" stroke-width="10" fill="none"/>
    <circle cx="62" cy="50" r="22" stroke="#F97316" stroke-width="10" fill="none"/>
    <path d="M 50 32 C 43 32 38 37 38 44 C 38 54 50 70 50 70 C 50 70 62 54 62 44 C 62 37 57 32 50 32 Z" fill="#10B981"/>
    <circle cx="50" cy="44" r="3.5" fill="#FFFFFF"/>
</svg>
