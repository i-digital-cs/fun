@props([
    'events',
    'joinActionsById' => [],
    'favoritedIds' => [],
    'user' => null,
    'cityName' => null,
    'nationwideFallback' => false,
])

@php
    $canCreate = $user?->canParticipate() === true;
    $discoverTitle = $cityName
        ? __('design.discover_title', ['city' => $cityName])
        : __('design.discover_title_generic');
    $eventItems = $events instanceof \Illuminate\Contracts\Pagination\Paginator || $events instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $events->getCollection()
        : collect($events);
@endphp

<section class="mt-12">
    @if ($nationwideFallback)
        <p class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ __('events.nationwide_notice') }}</p>
    @endif

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="flex items-center gap-2.5 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                {{ $discoverTitle }}
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-orange-500"></span>
                </span>
            </h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('design.discover_sub') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('events.index') }}" class="flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-xs font-bold text-slate-600 backdrop-blur transition hover:border-indigo-300 hover:text-indigo-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/>
                </svg>
                {{ __('home.filters') }}
            </a>
            <a href="{{ route('events.index') }}" class="flex items-center gap-1.5 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-xs font-bold text-slate-600 backdrop-blur transition hover:border-indigo-300 hover:text-indigo-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                {{ __('design.view_map') }}
            </a>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($eventItems as $event)
            <x-events.card
                :event="$event"
                :join-action="$joinActionsById[$event->id] ?? null"
                :favorited="isset($favoritedIds[$event->id])"
            />
        @endforeach

        <article class="group relative flex flex-col overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-[#FF762E] via-[#FF8A3D] to-[#E85F1A] p-6 text-white shadow-[0_22px_44px_-20px_rgba(255,118,46,0.55)] transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_28px_50px_-18px_rgba(255,118,46,0.65)]">
            <div aria-hidden="true" class="absolute inset-0">
                <div class="absolute -end-10 -top-10 h-44 w-44 rounded-full bg-white/15 blur-2xl"></div>
                <div class="absolute -bottom-14 -start-10 h-48 w-48 rounded-full bg-amber-200/25 blur-2xl"></div>
                <div class="absolute end-6 top-6 h-16 w-16 rounded-full border-2 border-dashed border-white/35 transition-transform duration-500 group-hover:rotate-45"></div>
            </div>

            <div class="relative flex flex-1 flex-col gap-3">
                <div class="flex items-center justify-between">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 backdrop-blur transition-transform duration-300 group-hover:scale-110">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>
                    <span class="rounded-full bg-white/20 px-3 py-1 text-[10px] font-black uppercase tracking-wider backdrop-blur">
                        {{ __('design.gather_chip') }}
                    </span>
                </div>
                <h3 class="text-xl font-extrabold leading-snug tracking-tight">{{ __('design.gather_title') }}</h3>
                <p class="text-sm leading-relaxed text-orange-50/95">{{ __('design.gather_body') }}</p>
                <ul class="mt-1 space-y-2">
                    @foreach (['gather_check1', 'gather_check2', 'gather_check3'] as $checkKey)
                        <li class="flex items-center gap-2 text-xs font-semibold text-orange-50">
                            <span class="flex h-[1.125rem] w-[1.125rem] shrink-0 items-center justify-center rounded-full bg-white/25">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                    <path stroke-linecap="round" d="M5 12l5 5L20 7"/>
                                </svg>
                            </span>
                            {{ __('design.' . $checkKey) }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ $canCreate ? route('events.create') : route('register') }}"
                   class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl bg-white py-3 text-sm font-extrabold text-[#E85F1A] shadow-xl transition hover:scale-[1.02] active:scale-95">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    {{ __('design.gather_cta') }}
                </a>
            </div>
        </article>
    </div>
</section>
