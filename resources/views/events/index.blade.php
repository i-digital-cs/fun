@extends('layouts.app')

@section('title', __('events.discover'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('events.discover') }}</h1>
            <p class="mt-1 text-sm text-muted">{{ __('events.discover_subtitle') }}</p>
        </div>
        @auth
            @if (auth()->user()->canParticipate())
                <a href="{{ route('events.create') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('events.create_event') }}</a>
            @endif
        @endauth
    </div>

    <form method="GET" action="{{ route('events.index') }}" class="mb-6 grid gap-3 rounded-2xl border border-border bg-surface p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label for="q" class="mb-1 block text-xs font-medium text-muted">{{ __('events.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="{{ __('events.search_placeholder') }}" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
        </div>
        <div>
            <label for="category_id" class="mb-1 block text-xs font-medium text-muted">{{ __('events.category') }}</label>
            <select id="category_id" name="category_id" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
                <option value="">{{ __('events.all_categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $filters['category_id'] === $category->id)>{{ $category->localizedName() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="city_id" class="mb-1 block text-xs font-medium text-muted">{{ __('events.city') }}</label>
            <select id="city_id" name="city_id" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
                <option value="">{{ __('events.all_cities') }}</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected((int) $filters['city_id'] === $city->id)>{{ $city->localizedName() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="distance_km" class="mb-1 block text-xs font-medium text-muted">{{ __('location.discovery_distance') }}</label>
            <select id="distance_km" name="distance_km" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
                @foreach ($distances as $distance)
                    <option value="{{ $distance }}" @selected((int) $filters['distance_km'] === $distance)>{{ $distance }} {{ __('location.km') }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">{{ __('events.apply_filters') }}</button>
            <a href="{{ route('events.index') }}" class="rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('events.clear_filters') }}</a>
            @auth
                @unless (auth()->user()->city_id)
                    <a href="{{ route('settings.location.edit') }}" class="text-sm text-primary hover:underline">{{ __('events.set_location_cta') }}</a>
                @endunless
            @endauth
        </div>
    </form>

    @if (! $hasNearbyOrigin)
        <p class="mb-4 rounded-lg border border-info/30 bg-info/10 px-4 py-3 text-sm text-info">{{ __('events.nationwide_notice') }}</p>
    @endif

    @if ($events->isEmpty())
        <p class="text-muted">{{ __('events.empty_nearby') }}</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events as $event)
                <x-events.card :event="$event" :join-action="$joinActionsById[$event->id] ?? null" />
            @endforeach
        </div>
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endsection
