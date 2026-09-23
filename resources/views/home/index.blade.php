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

    <section class="mt-8 overflow-hidden rounded-[1.75rem] border border-slate-200/70 bg-white/80 p-5 shadow-sm shadow-slate-900/5 backdrop-blur sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ __('weather.section_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('weather.section_sub') }}</p>
            </div>
            @auth
                <a href="{{ route('settings.location.edit') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">{{ __('weather.change_city') }}</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800">{{ __('weather.sign_in_for_local') }}</a>
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
        :join-actions-by-id="$joinActionsById"
        :favorited-ids="$favoritedIds ?? []"
        :user="$user"
        :city-name="$cityName"
        :nationwide-fallback="$nationwideFallback"
    />

    <x-community.trust />
@endsection
