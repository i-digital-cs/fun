@props([
    'event',
    'joinAction' => null,
    'favorited' => false,
])

@php
    $members = (int) $event->members_count;
    $max = max(1, (int) $event->max_participants);
    $pct = (int) min(100, round(($members / $max) * 100));
    $urgent = $pct >= 80;
    $categoryName = $event->category?->localizedName() ?? '';
    $emoji = category_emoji($categoryName);
    $isFree = blank($event->cost_per_person) || (float) $event->cost_per_person <= 0;
    $priceLabel = $isFree
        ? __('events.free')
        : __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]);
    $action = $joinAction?->value;
    $cover = $event->coverUrl();
    $schedule = $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('D · M j · g:i A');
@endphp

<div {{ $attributes->class(['event-3d']) }}>
    <article class="event-3d__card">
        <div class="event-3d__media">
            <a href="{{ route('events.show', $event) }}" class="block h-full" tabindex="-1" aria-hidden="true">
                <img src="{{ $cover }}" alt="" loading="lazy">
            </a>
            <div class="event-3d__media-fade" aria-hidden="true"></div>
        </div>

        <span class="event-3d__chip">
            <span aria-hidden="true">{{ $emoji }}</span>
            {{ $categoryName }}
        </span>
        <span @class([
            'event-3d__price',
            'event-3d__price--free' => $isFree,
            'event-3d__price--paid' => ! $isFree,
        ])>{{ $priceLabel }}</span>

        <div class="event-3d__logo" aria-hidden="true">
            <span class="event-3d__circle event-3d__circle--1"></span>
            <span class="event-3d__circle event-3d__circle--2"></span>
            <span class="event-3d__circle event-3d__circle--3"></span>
            <span class="event-3d__circle event-3d__circle--4"></span>
            <span class="event-3d__circle event-3d__circle--5">
                <img src="{{ $cover }}" alt="">
            </span>
        </div>

        <div class="event-3d__glass" aria-hidden="true"></div>

        <div class="event-3d__favorite">
            <x-events.favorite-bookmark :event="$event" :favorited="$favorited" id-suffix="rich" />
        </div>

        <div class="event-3d__body">
            <h3 class="event-3d__title">
                <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
            </h3>
            @if ($schedule)
                <span class="event-3d__meta">{{ $schedule }}</span>
            @endif
            <span class="event-3d__text">{{ $event->venue }}</span>

            <div class="event-3d__members">
                <div class="event-3d__members-row">
                    <span class="event-3d__members-label">{{ __('design.members') }}</span>
                    <span class="event-3d__members-count">{{ $members }} / {{ $max }}</span>
                </div>
                <div class="event-3d__bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div @class(['event-3d__bar-fill', 'event-3d__bar-fill--urgent' => $urgent]) style="width: {{ $pct }}%"></div>
                </div>
            </div>

            @if (in_array($action, ['request_pending', 'joined', 'request_rejected', 'event_full'], true))
                <div class="event-3d__status">
                    @if ($action === 'request_pending')
                        <span class="block rounded-xl border border-slate-200/70 bg-white/80 px-3 py-1.5 text-center text-xs font-semibold text-slate-600">{{ __('events.request_pending') }}</span>
                        <form method="POST" action="{{ route('events.join.cancel', $event) }}" class="cancel-btn-slot mt-1.5 py-0.5" data-confirm="{{ __('ui.confirm_cancel_join') }}">
                            @csrf
                            <x-ui.cancel-btn type="submit" size="sm" :label="__('events.cancel_join')" />
                        </form>
                    @elseif ($action === 'joined')
                        <span class="block rounded-xl bg-emerald-50 px-3 py-1.5 text-center text-xs font-semibold text-emerald-700">{{ __('events.joined') }}</span>
                    @elseif ($action === 'request_rejected')
                        <span class="block rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-center text-xs font-semibold text-amber-700">{{ __('events.request_rejected') }}</span>
                    @elseif ($action === 'event_full')
                        <span class="block rounded-xl border border-slate-200/70 bg-white/80 px-3 py-1.5 text-center text-xs font-semibold text-slate-600">{{ __('events.event_full') }}</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="event-3d__bottom">
            <div class="event-3d__pills">
                <span class="event-3d__pill event-3d__pill--wide" title="{{ __('design.members') }}">{{ $members }}/{{ $max }}</span>
                <span class="event-3d__pill" title="{{ $priceLabel }}">{{ $isFree ? '✓' : __('events.currency_short') }}</span>
                <span class="event-3d__pill" title="{{ $pct }}%">{{ $pct }}%</span>
            </div>
            <a href="{{ route('events.show', $event) }}" class="event-3d__view">
                {{ __('events.view_event') }}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" class="rtl:rotate-180" aria-hidden="true">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        </div>
    </article>
</div>
