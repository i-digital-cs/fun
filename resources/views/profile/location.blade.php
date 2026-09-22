@extends('layouts.app')

@section('title', __('location.location_settings'))

@section('content')
    <div class="mx-auto max-w-lg rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('location.location_settings') }}</h1>

        <form method="POST" action="{{ route('settings.location.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="city_id">{{ __('location.your_city') }}</label>
                <select id="city_id" name="city_id" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    <option value="">—</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id', $user->city_id) == $city->id)>
                            {{ $city->localizedName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="distance_km">{{ __('location.discovery_distance') }}</label>
                <select id="distance_km" name="distance_km" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    @foreach ($distances as $distance)
                        <option value="{{ $distance }}" @selected((int) old('distance_km', $user->distance_km ?? config('location.default_distance_km')) === $distance)>
                            {{ $distance }} {{ __('location.km') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                {{ __('location.save') }}
            </button>
        </form>
    </div>
@endsection
