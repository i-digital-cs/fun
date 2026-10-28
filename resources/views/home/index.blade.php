@extends('layouts.home')

@section('title', $appName)

@section('content')
    <x-community.hero
        :user="$user"
        :city-name="$cityName"
        :when="$when"
        :categories="$categories"
        :published-total="$publishedTotal"
        :highlight="$highlight"
        :stats="$stats"
    />

    <section class="mt-8 overflow-hidden rounded-[1.35rem] border border-[#E8EAF2] bg-white/90 p-5 shadow-[0_18px_40px_-28px_rgba(25,33,61,0.35)] backdrop-blur-xl sm:rounded-[1.75rem] sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-[#19213D] sm:text-2xl">{{ __('weather.section_title') }}</h2>
                <p class="mt-1 text-sm text-[#64748B]">{{ __('weather.section_sub') }}</p>
            </div>
            @auth
                <a href="{{ route('settings.location.edit') }}" class="text-sm font-bold text-[#7138F5] hover:text-[#5825D6]">{{ __('weather.change_city') }}</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-bold text-[#7138F5] hover:text-[#5825D6]">{{ __('weather.sign_in_for_local') }}</a>
            @endauth
        </div>
        <div class="mt-5 grid items-stretch gap-4 lg:grid-cols-2">
            <div class="min-w-0">
                <x-dashboard.weather-card class="h-full" :weather="$weather" />
            </div>
            <div class="min-w-0">
                <x-dashboard.now-playing
                    class="h-full"
                    :running="$nowPlaying['running'] ?? null"
                    :next="$nowPlaying['next'] ?? []"
                    :previous="$nowPlaying['previous'] ?? []"
                />
            </div>
        </div>
    </section>

    @auth
        @if ($user?->canParticipate())
            <x-community.participations
                :memberships="$memberships"
                :pending-requests="$pendingRequests"
            />
        @endif
    @endauth

    <x-community.discover
        :events="$events"
        :worth-the-trip="$worthTheTrip ?? []"
        :join-actions-by-id="$joinActionsById"
        :favorited-ids="$favoritedIds ?? []"
        :user="$user"
        :city-name="$cityName"
        :nationwide-fallback="$nationwideFallback"
        :discovery-expanded="$discoveryExpanded ?? false"
        :discovery-meta="$discoveryMeta ?? []"
    />

    @auth
        @if ($user?->canParticipate())
            <x-community.people-suggestions :suggestions="$peopleSuggestions ?? []" />
        @endif
    @endauth

    <x-community.trust />
@endsection
