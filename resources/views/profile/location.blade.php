@extends('layouts.app')

@section('title', __('location.location_settings'))

@section('content')
    <div class="mx-auto max-w-lg rounded-2xl border border-border bg-surface p-6">
        <h1 class="text-2xl font-semibold">{{ __('location.location_settings') }}</h1>

        <form method="POST" action="{{ route('settings.location.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium" for="city_id">{{ __('location.your_city') }}</label>
                <select id="city_id" name="city_id" required class="w-full rounded-lg border border-border px-3 py-2">
                    <option value="">—</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id', $user->city_id) == $city->id)>
                            {{ $city->localizedName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium" for="distance_km">{{ __('location.discovery_distance') }}</label>
                <select id="distance_km" name="distance_km" required class="w-full rounded-lg border border-border px-3 py-2">
                    @foreach ($distances as $distance)
                        <option value="{{ $distance }}" @selected((int) old('distance_km', $user->distance_km ?? config('location.default_distance_km')) === $distance)>
                            {{ $distance }} {{ __('location.km') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full rounded-lg bg-accent px-4 py-2.5 font-semibold text-white">
                {{ __('location.save') }}
            </button>
        </form>
    </div>
@endsection
