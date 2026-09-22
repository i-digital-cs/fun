@extends('layouts.app')

@section('title', __('events.discover'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.discover') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('events.discover_subtitle') }}</p>
        </div>
        @auth
            @if (auth()->user()->canParticipate())
                <a href="{{ route('events.create') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                    {{ __('events.create_event') }}
                </a>
            @endif
        @endauth
    </div>

    <form method="GET" action="{{ route('events.index') }}" class="mb-6 grid gap-4 rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label for="q" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('events.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="{{ __('events.search_placeholder') }}" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label for="category_id" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('events.category') }}</label>
            <select id="category_id" name="category_id" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                <option value="">{{ __('events.all_categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $filters['category_id'] === $category->id)>
                        {{ $category->localizedName() }}
                        @if (isset($category->published_events_count))
                            ({{ $category->published_events_count }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="city_id" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('events.city') }}</label>
            <select id="city_id" name="city_id" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                <option value="">{{ __('events.all_cities') }}</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected((int) $filters['city_id'] === $city->id)>{{ $city->localizedName() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="distance_km" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('location.discovery_distance') }}</label>
            <select id="distance_km" name="distance_km" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                @foreach ($distances as $distance)
                    <option value="{{ $distance }}" @selected((int) $filters['distance_km'] === $distance)>{{ $distance }} {{ __('location.km') }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-5">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:brightness-105">{{ __('events.apply_filters') }}</button>
            <a href="{{ route('events.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.clear_filters') }}</a>
            @auth
                @unless (auth()->user()->city_id)
                    <a href="{{ route('settings.location.edit') }}"
                       data-location-modal-open
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('events.set_location_cta') }}</a>
                @endunless
            @endauth
        </div>
    </form>

    @if (($nationwideFallback ?? false) || ! $hasNearbyOrigin)
        <p class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">{{ __('events.nationwide_notice') }}</p>
    @endif

    @if ($events->isEmpty())
        <div class="rounded-3xl border border-slate-200/70 bg-white p-8 text-center shadow-sm">
            <p class="text-slate-500">{{ __('events.empty_nearby') }}</p>
            @auth
                @if (auth()->user()->canParticipate())
                    <a href="{{ route('events.create') }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                        {{ __('events.create_event') }}
                    </a>
                @endif
            @endauth
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <x-events.card :event="$event" :join-action="$joinActionsById[$event->id] ?? null" />
            @endforeach
        </div>
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endsection
