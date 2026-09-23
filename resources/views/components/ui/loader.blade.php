@props([
    'label' => null,
    'size' => 'md', // sm | md | lg
    'showLabel' => true,
])

@php
    $text = $label ?? __('ui.loading');
    $sizeClass = match ($size) {
        'sm' => 'fun-loader--sm',
        'lg' => 'fun-loader--lg',
        default => 'fun-loader--md',
    };
@endphp

{{-- Uiverse.io by santhosh_4929 — system content / page loader --}}
<div {{ $attributes->class(['fun-loader', $sizeClass]) }} role="status" aria-live="polite" aria-busy="true">
    <div class="fun-loader__circles" aria-hidden="true">
        <span class="fun-loader__circle"></span>
        <span class="fun-loader__circle"></span>
        <span class="fun-loader__circle"></span>
        <span class="fun-loader__circle"></span>
        <span class="fun-loader__circle"></span>
        <span class="fun-loader__circle"></span>
    </div>
    @if ($showLabel)
        <p class="fun-loader__text">{{ $text }}</p>
    @endif
    <span class="sr-only">{{ $text }}</span>
</div>
