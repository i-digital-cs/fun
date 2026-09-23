@props([
    'label' => null,
    'size' => 'md', // sm | md
])

@php
    $text = $label ?? __('ui.searching');
    $sizeClass = $size === 'sm' ? 'wifi-loader--sm' : '';
@endphp

{{-- Uiverse.io by mobinkakei — only while typed query awaits API (not a page loader) --}}
<div {{ $attributes->class(['wifi-loader', $sizeClass]) }} role="status" aria-live="polite" aria-busy="true">
    <svg class="circle-outer" viewBox="0 0 86 86" aria-hidden="true">
        <circle class="back" cx="43" cy="43" r="40"></circle>
        <circle class="front" cx="43" cy="43" r="40"></circle>
        <circle class="new" cx="43" cy="43" r="40"></circle>
    </svg>
    <svg class="circle-middle" viewBox="0 0 60 60" aria-hidden="true">
        <circle class="back" cx="30" cy="30" r="27"></circle>
        <circle class="front" cx="30" cy="30" r="27"></circle>
    </svg>
    <svg class="circle-inner" viewBox="0 0 34 34" aria-hidden="true">
        <circle class="back" cx="17" cy="17" r="14"></circle>
        <circle class="front" cx="17" cy="17" r="14"></circle>
    </svg>
    <div class="text" data-text="{{ $text }}"></div>
    <span class="sr-only">{{ $text }}</span>
</div>
