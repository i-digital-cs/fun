@php
    $startsAt = $event->starts_at?->timezone(config('app.timezone'));
    $endsAt = $event->ends_at?->timezone(config('app.timezone'));
    $dateLabel = $startsAt
        ? ($startsAt->isToday()
            ? __('events.today').' · '.$startsAt->translatedFormat('g:i A')
            : ($startsAt->isTomorrow()
                ? __('events.tomorrow').' · '.$startsAt->translatedFormat('g:i A')
                : $startsAt->translatedFormat('D · M j · g:i A')))
        : null;
    $placeLabel = $event->locationLabel();
    $isFree = $event->cost_per_person === null || (float) $event->cost_per_person <= 0;
@endphp

<article
    class="event-qv-panel"
    data-event-qv-panel
    data-event-id="{{ $event->id }}"
    data-canonical-url="{{ $canonicalUrl }}"
    data-event-title="{{ $event->title }}"
>
    <div class="relative h-44 overflow-hidden sm:h-52">
        <x-events.cover-img :event="$event" size="detail" class="h-full w-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/25 to-transparent"></div>
        <div class="absolute end-3 top-3 z-10 rounded-full bg-white/90 p-1.5 shadow-lg backdrop-blur">
            <x-events.favorite-bookmark :event="$event" :favorited="$isFavorited" id-suffix="qv-{{ $event->id }}" />
        </div>
        <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
            @if ($event->category)
                <span class="mb-1.5 inline-flex items-center gap-1 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-slate-800">
                    <span aria-hidden="true">{{ category_emoji($event->category->localizedName()) }}</span>
                    {{ $event->category->localizedName() }}
                </span>
            @endif
            <h2 id="event-qv-title" class="text-xl font-extrabold tracking-tight text-white sm:text-2xl bidi-text" dir="{{ bidi_dir($event->title) }}">
                {{ filled($event->title) ? $event->title : __('events.view_event') }}
            </h2>
        </div>
    </div>

    <div class="space-y-4 p-4 sm:p-6">
        <div class="flex flex-wrap gap-2 text-sm text-slate-600">
            @if ($dateLabel)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-700">{{ $dateLabel }}
                    @if ($endsAt)
                        <span class="font-medium text-indigo-500">– {{ $endsAt->translatedFormat('g:i A') }}</span>
                    @endif
                </span>
            @endif
            @if ($placeLabel !== '')
                <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-700">
                    <span class="truncate bidi-text" dir="{{ bidi_dir($placeLabel) }}">{{ $placeLabel }}</span>
                </span>
            @endif
        </div>

        @if ($event->creator)
            <div class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-3 py-2.5">
                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                    {{ mb_substr($event->creator->name ?? '?', 0, 1) }}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('events.host') }}</p>
                    <p class="truncate text-sm font-bold text-slate-900">{{ $event->creator->name }}</p>
                </div>
            </div>
        @endif

        @if (filled($event->description))
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('events.description') }}</p>
                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-700 line-clamp-6">{{ $event->description }}</p>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            @if ($isFree)
                <span class="rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 px-3 py-1.5 text-xs font-semibold text-white">{{ __('events.free') }}</span>
            @else
                <span class="rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-1.5 text-xs font-semibold text-white">{{ __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]) }}</span>
            @endif
            <span class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">
                {{ $event->members_count }} / {{ $event->max_participants }} {{ __('events.members') }}
            </span>
        </div>

        <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
            @auth
                @if ($joinAction->value === 'request_to_join' || $joinAction->value === 'request_rejected')
                    <form method="POST" action="{{ event_route('events.join', $event) }}">
                        @csrf
                        <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30">{{ __('events.request_to_join') }}</button>
                    </form>
                @elseif ($joinAction->value === 'request_pending')
                    <span class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-500">{{ __('events.request_pending') }}</span>
                @elseif ($joinAction->value === 'joined')
                    <span class="inline-flex min-h-11 items-center rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">{{ __('events.joined') }}</span>
                    <a href="{{ event_route('events.ticket', $event) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white">{{ __('events.view_ticket') }}</a>
                @elseif ($joinAction->value === 'event_full')
                    <span class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-500">{{ __('events.event_full') }}</span>
                @elseif ($joinAction->value === 'sex_mismatch')
                    <span class="inline-flex min-h-11 items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800">{{ __('events.sex_mismatch') }}</span>
                @endif
            @else
                @if ($joinAction->value === 'request_to_join')
                    <a href="{{ route('login', ['returnTo' => parse_url($canonicalUrl, PHP_URL_PATH) ?: $canonicalUrl]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30">{{ __('events.request_to_join') }}</a>
                @endif
            @endauth

            <a
                href="{{ $canonicalUrl }}"
                data-event-qv-full
                class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-50"
            >
                {{ __('events.open_full_page') }}
            </a>
        </div>
    </div>
</article>
