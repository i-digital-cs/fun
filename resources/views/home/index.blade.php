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

    <section class="mx-auto max-w-6xl px-4 sm:px-6">
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
        <div class="mt-4 flex justify-center sm:justify-start">
            <x-dashboard.weather-card :weather="$weather" />
        </div>
        <div class="mt-5 max-w-md">
            <x-dashboard.now-playing
                :running="$nowPlaying['running'] ?? null"
                :next="$nowPlaying['next'] ?? []"
                :previous="$nowPlaying['previous'] ?? []"
            />
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
