@props([
    'title',
    'subtitle' => null,
    'id' => null,
])

<section @if ($id) id="{{ $id }}" aria-labelledby="{{ $id }}-heading" @endif {{ $attributes->class('mb-6 sm:mb-8') }}>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-2 sm:mb-4">
        <div class="min-w-0">
            <h2 @if ($id) id="{{ $id }}-heading" @endif class="text-lg font-bold tracking-tight text-[#19213d] sm:text-xl">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        {{ $actions ?? '' }}
    </div>
    {{ $slot }}
</section>
