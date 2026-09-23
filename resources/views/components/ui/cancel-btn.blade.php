@props([
    'label' => null,
    'stopLabel' => null,
    'type' => 'button',
    'size' => 'md', // sm | md | block
])

@php
    $label = $label ?? __('ui.cancel');
    $stopLabel = $stopLabel ?? __('ui.stop');
    $sizeClass = match ($size) {
        'sm' => 'cancel-btn--sm',
        'block' => 'cancel-btn--block',
        default => '',
    };
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class(['cancel-btn', $sizeClass]) }}
>
    <span class="cancel-btn__txt cancel-btn__txt--idle">{{ $label }}</span>
    <span class="cancel-btn__txt cancel-btn__txt--stop">{{ $stopLabel }}</span>
</button>
