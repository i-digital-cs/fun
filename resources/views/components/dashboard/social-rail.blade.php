@props([
    'user',
    'cityName' => null,
    'distanceKm' => null,
    'memberships' => collect(),
    'pendingRequests' => collect(),
    'nowPlaying' => [],
    'createRoute',
])

@php
    $upcoming = collect($memberships)->take(3);
    $running = $nowPlaying['running'] ?? null;
    $liveList = collect();
    if ($running) {
        $liveList->push($running);
    }
    foreach (($nowPlaying['next'] ?? []) as $item) {
        if ($liveList->count() >= 3) break;
        $liveList->push($item);
    }
    $cityLabel = $cityName ?: __('home.location_default');
    $km = (int) ($distanceKm ?? config('location.default_distance_km'));
@endphp

<div class="flex flex-col gap-4">
    {{-- Your location --}}
    <a href="{{ route('settings.location.edit') }}"
       data-location-modal-open
       class="flex items-center gap-3 rounded-[1.1rem] border border-[#E8EAF2] bg-white p-3.5 no-underline shadow-sm transition hover:border-[#7138F5]/30">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#7138F5]/10 text-[#7138F5]">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                <circle cx="12" cy="9" r="2.5"/>
            </svg>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ __('dashboard.your_location') }}</span>
            <span class="block truncate text-sm font-extrabold text-[#19213D]">{{ $cityLabel }}</span>
            <span class="block text-[11px] text-slate-500">
                @if ((int) $km === 0)
                    {{ __('location.all_distances') }}
                @else
                    {{ __('dashboard.showing_within', ['km' => $km]) }}
                @endif
            </span>
        </span>
        <span class="text-xs font-bold text-[#7138F5]">{{ __('dashboard.change') }}</span>
    </a>

    {{-- Upcoming participations --}}
    <div class="rounded-[1.1rem] border border-[#E8EAF2] bg-white p-3.5 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h3 class="text-sm font-extrabold text-[#19213D]">{{ __('dashboard.upcoming_title') }}</h3>
            <a href="{{ route('events.mine') }}" class="text-[11px] font-bold text-[#7138F5] no-underline">{{ __('dashboard.see_all') }}</a>
        </div>
        @forelse ($upcoming as $member)
            @php
                $event = $member->event ?? null;
            @endphp
            @if ($event)
                @php
                    $thumb = $event->coverUrl('card');
                    $starts = $event->starts_at?->timezone(config('app.timezone'));
                    $when = $starts ? $starts->translatedFormat('D · g:i A') : '';
                @endphp
                <a href="{{ event_show_url($event) }}" class="mb-2.5 flex items-center gap-2.5 last:mb-0 no-underline">
                    @if ($thumb)
                        <img src="{{ $thumb }}" alt="" class="h-11 w-11 rounded-xl object-cover" width="44" height="44" loading="lazy">
                    @else
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#EEF2FF] text-base">{{ category_emoji($event->category?->localizedName() ?? '') }}</span>
                    @endif
                    <span class="min-w-0">
                        <span class="block truncate text-[12px] font-bold text-[#19213D]" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</span>
                        <span class="block text-[10px] text-slate-500">{{ $when }}</span>
                    </span>
                </a>
            @endif
        @empty
            <p class="text-[12px] text-slate-500">{{ __('dashboard.upcoming_empty') }}</p>
        @endforelse
    </div>

    {{-- Host CTA --}}
    <div class="rounded-[1.1rem] bg-gradient-to-br from-[#7138F5] to-[#5825D6] p-4 text-white shadow-[0_16px_32px_-18px_rgba(113,56,245,0.65)]">
        <p class="text-sm font-extrabold leading-snug">{{ __('dashboard.host_cta_title') }}</p>
        <p class="mt-1 text-[11px] text-white/80">{{ __('dashboard.host_cta_body') }}</p>
        <a href="{{ $createRoute }}"
           class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-white px-3.5 py-2 text-[12px] font-extrabold text-[#7138F5] no-underline">
            + {{ __('events.create_event') }}
        </a>
    </div>

    {{-- Live / up next --}}
    <div class="rounded-[1.1rem] border border-[#E8EAF2] bg-white p-3.5 shadow-sm">
        <h3 class="mb-3 text-sm font-extrabold text-[#19213D]">{{ __('dashboard.now_playing_title', ['brand' => site_display_name()]) }}</h3>
        @forelse ($liveList as $event)
            @if ($event)
                @php
                    $isLive = $running && (int) $running->id === (int) $event->id;
                @endphp
                <a href="{{ event_show_url($event) }}" class="mb-2.5 block last:mb-0 no-underline">
                    <span class="block truncate text-[12px] font-bold text-[#19213D]" dir="{{ bidi_dir($event->title) }}">{{ $event->title }}</span>
                    <span class="text-[10px] font-semibold {{ $isLive ? 'text-emerald-600' : 'text-slate-500' }}">
                        {{ $isLive ? __('dashboard.now_playing_live') : __('dashboard.now_playing_up_next') }}
                    </span>
                </a>
            @endif
        @empty
            <p class="text-[12px] text-slate-500">{{ __('dashboard.now_playing_idle') }}</p>
        @endforelse
    </div>

    {{-- Friends entry (no fabricated communities) --}}
    <div class="rounded-[1.1rem] border border-[#E8EAF2] bg-white p-3.5 shadow-sm">
        <h3 class="mb-2 text-sm font-extrabold text-[#19213D]">{{ __('dashboard.social_circle_title') }}</h3>
        <p class="text-[12px] leading-relaxed text-slate-500">{{ __('dashboard.social_circle_body') }}</p>
        <a href="{{ route('friends.index') }}" class="mt-3 inline-flex text-[12px] font-extrabold text-[#7138F5] no-underline">
            {{ __('friends.title') }} →
        </a>
    </div>
</div>
