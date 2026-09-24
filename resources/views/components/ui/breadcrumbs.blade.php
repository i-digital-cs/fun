@props([
    'items' => [],
])

@php
    /** @var list<array{label: string, url?: string|null}> $items */
@endphp

@if (count($items) > 0)
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500">
            @foreach ($items as $index => $item)
                <li class="inline-flex items-center gap-1.5">
                    @if ($index > 0)
                        <svg class="h-3 w-3 text-slate-300 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                        </svg>
                    @endif
                    @if (! empty($item['url']) && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="transition hover:text-indigo-600">{{ $item['label'] }}</a>
                    @else
                        <span @class(['text-slate-800' => $loop->last]) @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
