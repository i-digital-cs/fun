@props([
    'events',
    'joinActionsById' => [],
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
            @php
                $joined = $event->acceptedMembersCount();
                $total = max(1, (int) $event->max_participants);
                $pct = (int) round(($joined / $total) * 100);
                $urgent = $pct >= 80;
                $categoryName = $event->category?->localizedName() ?? '';
                $emoji = category_emoji($categoryName);
                $isFree = blank($event->cost_per_person) || (float) $event->cost_per_person <= 0;
                $priceLabel = $isFree
                    ? __('design.free')
                    : number_format((float) $event->cost_per_person, 0) . ' ' . config('app.currency', 'EGP');

                if ($pct >= 80) {
                    $urgencyKey = 'almost_full';
                    $urgencyClass = 'text-orange-600';
                } elseif ($pct >= 60) {
                    $urgencyKey = 'filling_fast';
                    $urgencyClass = 'text-rose-600';
                } elseif ($event->created_at && $event->created_at->gte(now()->subDays(3))) {
                    $urgencyKey = 'new_badge';
                    $urgencyClass = 'text-emerald-600';
                } else {
                    $urgencyKey = 'popular';
                    $urgencyClass = 'text-violet-600';
                }

                $hostInitials = user_initials($event->creator?->name);
            @endphp

            <article class="group relative flex flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-indigo-600/10">
                <div class="relative h-44 overflow-hidden">
                    <img src="{{ event_cover_url($event->imageUrl(), $event->id) }}"
                         alt="{{ $event->title }}"
                         class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/50 via-transparent to-slate-950/10"></div>

                    <span class="absolute start-3 top-3 flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-bold text-slate-800 shadow-md backdrop-blur">
                        <span class="text-sm leading-none">{{ $emoji }}</span>
                        {{ $categoryName }}
                    </span>
                    <span @class([
                        'absolute end-3 top-3 rounded-full px-3 py-1.5 text-[11px] font-black text-white shadow-lg',
                        'bg-gradient-to-r from-emerald-500 to-teal-500 shadow-emerald-600/40' => $isFree,
                        'bg-gradient-to-r from-orange-500 to-amber-500 shadow-orange-600/40' => ! $isFree,
                    ])>
                        {{ $priceLabel }}
                    </span>
                    <span class="absolute bottom-3 start-3 flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-black shadow backdrop-blur">
                        @if ($urgencyKey === 'almost_full')
                            <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73 0-2.15.74-4.8.74-4.8S10.5 0 12 0s1.5.67 1.5.67z"/>
                            </svg>
                        @elseif ($urgencyKey === 'filling_fast')
                            <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/>
                            </svg>
                        @elseif ($urgencyKey === 'new_badge')
                            <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                            </svg>
                        @else
                            <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M3 17l6-6 4 4 8-8"/>
                            </svg>
                        @endif
                        <span class="{{ $urgencyClass }}">{{ __('design.' . $urgencyKey) }}</span>
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-3 p-5">
                    <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-indigo-600">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                        {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('D · M j · g:i A') }}
                    </span>
                    <h3 class="text-base font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
                        {{ $event->title }}
                    </h3>
                    <p class="flex items-center gap-1.5 truncate text-xs text-slate-500">
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                        </svg>
                        {{ $event->venue }}
                    </p>

                    <div class="mt-1 rounded-2xl border border-slate-100 bg-slate-50/80 p-3">
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-500">{{ __('design.members') }}</span>
                            <span class="font-black text-slate-800">{{ $joined }} / {{ $total }}</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                            <div @class([
                                'h-full rounded-full',
                                'bg-gradient-to-r from-orange-500 to-amber-500' => $urgent,
                                'bg-gradient-to-r from-indigo-500 to-violet-500' => ! $urgent,
                            ]) style="width: {{ min(100, $pct) }}%"></div>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between">
                            <div class="flex -space-x-2 rtl:space-x-reverse">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-indigo-500 to-violet-600 text-[9px] font-bold text-white" style="z-index: 3">{{ $hostInitials }}</span>
                                @if ($joined > 1)
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-orange-400 to-amber-500 text-[9px] font-bold text-white" style="z-index: 2">MK</span>
                                @endif
                                @if ($joined > 2)
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-emerald-400 to-teal-500 text-[9px] font-bold text-white" style="z-index: 1">RD</span>
                                @endif
                                @if ($joined > 3)
                                    <span class="flex h-6 items-center rounded-full border-2 border-white bg-slate-100 px-1.5 text-[9px] font-bold text-slate-500">+{{ $joined - 3 }}</span>
                                @endif
                            </div>
                            <span class="text-[10px] font-semibold text-slate-400">{{ $event->creator?->name }}</span>
                        </div>
                    </div>

                    <a href="{{ route('events.show', $event) }}" class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105 active:scale-[0.98]">
                        {{ __('events.view_event') }}
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </article>
        @endforeach

        <article class="group relative flex flex-col overflow-hidden rounded-3xl bg-gradient-to-br from-orange-500 via-orange-500 to-amber-600 p-6 text-white shadow-xl shadow-orange-500/25 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-orange-500/40">
            <div aria-hidden="true" class="absolute inset-0">
                <div class="absolute -end-10 -top-10 h-44 w-44 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-14 -start-10 h-48 w-48 rounded-full bg-amber-300/20 blur-2xl"></div>
                <div class="absolute end-6 top-6 h-16 w-16 rounded-full border-2 border-dashed border-white/30 transition-transform duration-500 group-hover:rotate-45"></div>
            </div>

            <div class="relative flex flex-1 flex-col gap-3">
                <div class="flex items-center justify-between">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 backdrop-blur transition-transform duration-300 group-hover:scale-110">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>
                    <span class="rounded-full bg-white/20 px-3 py-1 text-[10px] font-black uppercase tracking-wider backdrop-blur">
                        {{ __('design.gather_chip') }}
                    </span>
                </div>
                <h3 class="text-xl font-extrabold leading-snug tracking-tight">{{ __('design.gather_title') }}</h3>
                <p class="text-sm leading-relaxed text-orange-50/90">{{ __('design.gather_body') }}</p>
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
                   class="mt-auto flex w-full items-center justify-center gap-2 rounded-xl bg-white py-3 text-sm font-extrabold text-orange-600 shadow-xl transition hover:scale-[1.02] active:scale-95">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                    {{ __('design.gather_cta') }}
                </a>
            </div>
        </article>
    </div>
</section>
