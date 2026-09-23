@props([
    'tone' => 'indigo', // indigo | amber | emerald
    'size' => 'md', // sm | md | lg
    'arms' => 3, // 2–4 spiral arms
    'dots' => 12, // dots per arm (full Uiverse uses 20)
])

@php
    $toneClass = match ($tone) {
        'amber' => 'espiral--amber',
        'emerald' => 'espiral--emerald',
        default => 'espiral--indigo',
    };
    $sizeClass = match ($size) {
        'sm' => 'espiral--sm',
        'lg' => 'espiral--lg',
        default => 'espiral--md',
    };
    $arms = max(2, min(4, (int) $arms));
    $dots = max(8, min(20, (int) $dots));
@endphp

{{-- Uiverse.io by 20essentials — ambient vibe only (pointer-events none, behind content) --}}
<section
    {{ $attributes->class(['espiral', $toneClass, $sizeClass]) }}
    aria-hidden="true"
>
    @for ($r = 1; $r <= $arms; $r++)
        <aside class="espiral__loader" style="--r: {{ $r }}">
            @for ($i = 1; $i <= $dots; $i++)
                <span class="espiral__dot" style="--i: {{ $i }}"></span>
            @endfor
        </aside>
    @endfor
</section>
