@extends('layouts.dashboard')

@section('title', filled($event->title) ? $event->title : __('events.view_event'))

@section('content')
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
    $statusLabel = match ($event->status->value) {
        'published' => __('events.status_published'),
        'draft' => __('events.status_draft'),
        'cancelled' => __('events.status_cancelled'),
        'completed' => __('events.status_completed'),
        default => $event->status->value,
    };
    $statusClass = match ($event->status->value) {
        'published' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'draft' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
        'completed' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<article class="mx-auto max-w-4xl space-y-6">
    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200/70 bg-white shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
        <div class="relative h-56 overflow-hidden sm:h-72 md:h-80">
            <img src="{{ event_cover_url($event->imageUrl(), $event->id) }}"
                 alt=""
                 class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-slate-950/20 to-transparent"></div>
            <x-ui.espiral tone="indigo" size="md" :arms="3" :dots="12" class="espiral--anchor-cover" />

            <div class="absolute end-4 top-4 z-10 rounded-full bg-white/90 p-2 shadow-lg backdrop-blur">
                <x-events.favorite-bookmark :event="$event" :favorited="$isFavorited" id-suffix="show" />
            </div>

            <div class="absolute inset-x-0 bottom-0 flex flex-wrap items-end justify-between gap-3 p-5 sm:p-7">
                <div class="min-w-0">
                    @if ($event->category)
                        <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-bold text-slate-800 shadow-md backdrop-blur">
                            <span class="text-sm leading-none">{{ category_emoji($event->category->localizedName()) }}</span>
                            {{ $event->category->localizedName() }}
                        </span>
                    @endif
                    @if ($event->city)
                        <p class="text-sm font-semibold text-indigo-200">{{ $event->city->localizedName() }}</p>
                    @endif
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl md:text-4xl">
                        {{ filled($event->title) ? $event->title : __('events.view_event') }}
                    </h1>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        <div class="space-y-5 p-5 sm:p-8">
            <div class="flex flex-wrap gap-3 text-sm text-slate-600">
                @if ($dateLabel)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 font-semibold text-indigo-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                        {{ $dateLabel }}
                        @if ($endsAt)
                            <span class="font-medium text-indigo-500">– {{ $endsAt->translatedFormat('g:i A') }}</span>
                        @endif
                    </span>
                @endif
                @if (filled($event->venue))
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-700">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
                        </svg>
                        {{ $event->venue }}
                    </span>
                @endif
            </div>

            @if (filled($event->description))
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600 sm:text-[15px]">{{ $event->description }}</p>
            @endif

            @if (filled($event->requirements))
                <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('events.requirements') }}</h2>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $event->requirements }}</p>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                @if ($event->expected_cost)
                    <span class="rounded-full border border-slate-200/70 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">{{ __('events.expected_cost') }}: {{ $event->expected_cost }}</span>
                @endif
                @if ($event->cost_per_person !== null)
                    <span class="rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-1.5 text-sm font-semibold text-white">{{ __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]) }}</span>
                @else
                    <span class="rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 px-3 py-1.5 text-sm font-semibold text-white">{{ __('events.free') }}</span>
                @endif
                <span class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">
                    {{ $event->members_count }} / {{ $event->max_participants }} {{ __('events.members') }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                @auth
                    @if ($joinAction->value === 'request_to_join' || $joinAction->value === 'request_rejected')
                        <form method="POST" action="{{ route('events.join', $event) }}">
                            @csrf
                            <button class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">{{ __('events.request_to_join') }}</button>
                        </form>
                        @if ($joinAction->value === 'request_rejected')
                            <span class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-700">{{ __('events.request_rejected') }}</span>
                        @endif
                    @elseif ($joinAction->value === 'request_pending')
                        <span class="inline-flex items-center rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-500">{{ __('events.request_pending') }}</span>
                        <form method="POST" action="{{ route('events.join.cancel', $event) }}" class="cancel-btn-slot" data-confirm="{{ __('ui.confirm_cancel_join') }}">
                            @csrf
                            <x-ui.cancel-btn type="submit" :label="__('events.cancel_join')" />
                        </form>
                    @elseif ($joinAction->value === 'joined')
                        <span class="inline-flex items-center rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">{{ __('events.joined') }}</span>
                        <a href="{{ route('events.ticket', $event) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">{{ __('events.view_ticket') }}</a>
                        <a href="{{ route('events.chat', $event) }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:brightness-105">{{ __('chat.open_chat') }}</a>
                        <a href="{{ route('events.members', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.members') }}</a>
                        @unless($event->isOwnedBy(auth()->user()))
                            <form method="POST" action="{{ route('events.leave', $event) }}" class="cancel-btn-slot" data-confirm="{{ __('ui.confirm_leave_event') }}">
                                @csrf
                                <x-ui.cancel-btn type="submit" :label="__('events.leave_event')" />
                            </form>
                        @endunless
                    @elseif ($joinAction->value === 'event_full')
                        <span class="inline-flex items-center rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-500">{{ __('events.event_full') }}</span>
                    @endif

                    @can('reviewJoinRequests', $event)
                        <a href="{{ route('events.requests', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.join_requests') }}</a>
                        <a href="{{ route('events.members', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.manage_members') }}</a>
                    @endcan

                    @can('update', $event)
                        <a href="{{ route('events.edit', $event) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.edit_event') }}</a>
                        @if ($event->status->value === 'draft')
                            <form method="POST" action="{{ route('events.publish', $event) }}">@csrf<button class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">{{ __('events.publish') }}</button></form>
                        @endif
                        @if (in_array($event->status->value, ['draft', 'published'], true))
                            <form method="POST" action="{{ route('events.cancel', $event) }}" class="cancel-btn-slot" data-confirm="{{ __('ui.confirm_cancel_event') }}">
                                @csrf
                                <x-ui.cancel-btn type="submit" :label="__('events.cancel')" />
                            </form>
                        @endif
                        @if ($event->status->value === 'published')
                            <form method="POST" action="{{ route('events.complete', $event) }}">@csrf<button class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">{{ __('events.complete') }}</button></form>
                        @endif
                    @endcan
                @else
                    @if ($joinAction->value === 'request_to_join')
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">{{ __('events.request_to_join') }}</a>
                    @elseif ($joinAction->value === 'event_full')
                        <span class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-500">{{ __('events.event_full') }}</span>
                    @endif
                @endauth
            </div>

            @auth
                @if (auth()->user()->canParticipate())
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        <a href="{{ route('reports.create', ['reportable_type' => 'event', 'reportable_id' => $event->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-red-200 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            {{ __('safety.report') }}
                        </a>
                        @if ($event->created_by !== auth()->id())
                            <form method="POST" action="{{ route('users.block', $event->created_by) }}">
                                @csrf
                                <button class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('safety.block') }}</button>
                            </form>
                        @endif
                    </div>
                @endif
            @endauth
        </div>
    </div>

            @if ($event->creator)
        <section class="rounded-[1.75rem] border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('ratings.organizer_rating') }}</h2>
            <div class="mt-3 flex items-center gap-3">
                @if ($event->creator->public_id)
                    <a href="{{ route('users.public.show', $event->creator->public_id) }}" class="flex items-center gap-3 no-underline">
                        @if ($event->creator->profile_image_path)
                            <img src="{{ $event->creator->profile_image_path }}" alt="" class="h-11 w-11 rounded-full object-cover shadow-md shadow-indigo-600/25">
                        @else
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-bold text-white shadow-md shadow-indigo-600/25">{{ user_initials($event->creator->name) }}</span>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-slate-800 hover:text-indigo-700">{{ $event->creator->name }}</p>
                            <p class="text-xs font-medium text-indigo-500">{{ __('profile.view_profile') }}</p>
                        </div>
                    </a>
                @else
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-bold text-white shadow-md shadow-indigo-600/25">{{ user_initials($event->creator->name) }}</span>
                    <p class="text-sm font-semibold text-slate-800">{{ $event->creator->name }}</p>
                @endif
            </div>
            @if (($creatorSummary['count'] ?? 0) > 0)
                <p class="mt-3 text-sm font-medium text-slate-900">
                    {{ __('ratings.average', ['average' => $creatorSummary['average'], 'count' => $creatorSummary['count']]) }}
                </p>
            @else
                <p class="mt-3 text-sm text-slate-500">{{ __('ratings.no_ratings_yet') }}</p>
            @endif
        </section>
    @endif

    @if ($canRate ?? false)
        <section class="rounded-[1.75rem] border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('ratings.title') }}</h2>
            @if ($userRating)
                <p class="mt-1 text-sm text-slate-500">{{ __('ratings.your_rating') }}: {{ $userRating->stars }}/5</p>
            @endif
            <form method="POST" action="{{ route('events.ratings.store', $event) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="stars" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('ratings.stars') }}</label>
                    <select id="stars" name="stars" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected((int) old('stars', $userRating?->stars ?? 5) === $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="comment" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('ratings.comment') }}</label>
                    <textarea id="comment" name="comment" rows="3" maxlength="1000" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">{{ old('comment', $userRating?->comment) }}</textarea>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">
                    {{ $userRating ? __('ratings.update') : __('ratings.submit') }}
                </button>
            </form>
        </section>
    @endif

    @if (($ratings ?? collect())->isNotEmpty())
        <section class="rounded-[1.75rem] border border-slate-200/70 bg-white/90 p-6 shadow-sm backdrop-blur">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('ratings.ratings') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($ratings as $rating)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ $rating->rater?->name }}</p>
                            <p class="text-sm font-medium text-indigo-600">{{ $rating->stars }}/5</p>
                        </div>
                        @if ($rating->comment)
                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ $rating->comment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
