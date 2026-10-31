@props([
    'title',
    'subtitle' => null,
    'id' => null,
])

<section @if ($id) id="{{ $id }}" aria-labelledby="{{ $id }}-heading" @endif {{ $attributes->class('mb-8') }}>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
        <div class="min-w-0">
            <h2 @if ($id) id="{{ $id }}-heading" @endif class="text-base font-extrabold tracking-tight text-[#19213d]">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $actions ?? '' }}
    </div>
    {{ $slot }}
</section>
