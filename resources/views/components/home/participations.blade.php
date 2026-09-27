@props([
    'memberships',
    'pendingRequests',
])

@php
    $limit = 4;
    $memberships = collect($memberships)->filter(fn ($m) => $m->event)->values();
    $pendingRequests = collect($pendingRequests)->filter(fn ($r) => $r->event)->values();
    $scheduledCount = $memberships->count() + $pendingRequests->count();
    $previewMemberships = $memberships->take($limit);
    $remainingSlots = max(0, $limit - $previewMemberships->count());
    $previewPending = $pendingRequests->take($remainingSlots);
    $hiddenCount = max(0, $scheduledCount - $previewMemberships->count() - $previewPending->count());
@endphp

<section class="mb-4 space-y-4" aria-labelledby="home-participations-heading">
    <div class="flex items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex text-primary" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
            </span>
            <h2 id="home-participations-heading" class="text-lg font-semibold text-text sm:text-xl">
                {{ __('home.your_participations') }}
            </h2>
            <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                {{ trans_choice('home.scheduled_count', $scheduledCount, ['count' => $scheduledCount]) }}
            </span>
        </div>
        <a href="{{ route('events.mine') }}" class="inline-flex items-center gap-0.5 text-sm font-semibold text-primary hover:underline">
            {{ __('home.manage_all') }}
            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    @if ($scheduledCount === 0)
        <div class="rounded-2xl border border-dashed border-border bg-surface px-5 py-8 text-center">
            <p class="text-sm font-medium text-text">{{ __('home.participations_empty') }}</p>
            <a href="{{ route('events.index') }}" class="mt-3 inline-flex rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">
                {{ __('events.discover') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($previewMemberships as $membership)
                @php($event = $membership->event)
                <article class="flex h-full flex-col justify-between gap-2.5 rounded-2xl border border-border bg-surface p-3 shadow-sm">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-xs font-semibold text-success">
                                    <span class="h-1.5 w-1.5 rounded-full bg-success" aria-hidden="true"></span>
                                    {{ __('home.confirmed_attendance') }}
                                </span>
                                @if ($event->category)
                                    <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary-ink">
                                        {{ $event->category->localizedName() }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-muted">
                                {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('D · g:i A') }}
                            </span>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-text">
                                <a href="{{ event_show_url($event) }}" class="hover:text-primary bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</a>
                            </h3>
                            <p class="mt-1 text-sm text-muted bidi-text" dir="{{ bidi_dir($event->locationLabel()) }}">{{ $event->locationLabel() }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-2 rounded-lg bg-background px-3 py-2 text-xs">
                            <span class="inline-flex items-center gap-1 font-semibold text-success">{{ __('home.step_requested') }}</span>
                            <span class="h-px flex-1 bg-success/40" aria-hidden="true"></span>
                            <span class="inline-flex items-center gap-1 font-semibold text-success">{{ __('home.step_accepted') }}</span>
                            <span class="h-px flex-1 bg-border" aria-hidden="true"></span>
                            <span class="text-muted">{{ __('home.step_gathering') }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-text">{{ $event->creator?->name }}</p>
                            <p class="text-xs text-muted">{{ __('home.host') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ event_show_url($event) }}" class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm font-medium hover:border-primary/40">
                                {{ __('events.view_event') }}
                            </a>
                            <a href="{{ event_route('events.chat', $event) }}" class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white">
                                {{ __('home.open_chat') }}
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach

            @foreach ($previewPending as $joinRequest)
                @php($event = $joinRequest->event)
                <article class="flex h-full flex-col justify-between gap-2.5 rounded-2xl border border-border bg-surface p-3 shadow-sm">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent">
                                    {{ __('home.request_pending') }}
                                </span>
                                @if ($event->category)
                                    <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary-ink">
                                        {{ $event->category->localizedName() }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-muted">
                                {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('D · g:i A') }}
                            </span>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-text">
                                <a href="{{ event_show_url($event) }}" class="hover:text-primary bidi-text" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</a>
                            </h3>
                            <p class="mt-1 text-sm text-muted bidi-text" dir="{{ bidi_dir($event->locationLabel()) }}">{{ $event->locationLabel() }}</p>
                        </div>
                        <div class="rounded-lg bg-background px-3 py-2 text-sm text-muted">
                            {{ __('home.pending_review_notice') }}
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-text">{{ $event->creator?->name }}</p>
                            <p class="text-xs text-muted">{{ __('home.host') }}</p>
                        </div>
                        <a href="{{ event_show_url($event) }}" class="inline-flex items-center gap-1 rounded-lg border border-border bg-background px-3 py-1.5 text-sm font-medium hover:border-primary/40">
                            {{ __('events.view_event') }}
                            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </article>
            @endforeach

        @if ($hiddenCount > 0)
            <div class="col-span-full text-center">
                <a href="{{ route('events.mine') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                    {{ __('home.participations_more', ['count' => $hiddenCount]) }}
                </a>
            </div>
        @endif
        </div>
    @endif
</section>
