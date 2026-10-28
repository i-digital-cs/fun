@auth
@php
    $locationUser = auth()->user()->loadMissing('city');
    $locationDistances = config('location.distances_km', [5, 10, 25, 50, 100, 0]);
    $openOnError = $errors->has('city_id') || $errors->has('distance_km');
    $profileCityLabel = $locationUser->city?->localizedName() ?? '';
@endphp

<dialog id="location-modal"
        class="location-modal fixed inset-0 m-auto h-fit max-h-[min(90vh,44rem)] w-[min(100%-2rem,26rem)] overflow-visible rounded-3xl border-0 bg-transparent p-0 shadow-none outline-none backdrop:bg-slate-950/50"
        aria-labelledby="location-modal-title"
        data-cities-search-url="{{ url('/api/cities/search') }}"
        data-discover-url="{{ route('events.index') }}"
        data-selected-prefix="{{ __('location.selected') }}: "
        data-location-unavailable="{{ __('events.location_unavailable') }}"
        data-location-denied="{{ __('events.location_permission_denied') }}"
        data-location-timeout="{{ __('location.gps_timeout') }}"
        data-location-invalid="{{ __('location.gps_invalid') }}"
        data-low-accuracy="{{ __('location.gps_low_accuracy') }}"
        data-pick-city="{{ __('location.pick_from_results') }}"
        data-approx-accuracy="{{ __('location.approx_accuracy') }}"
        data-accuracy="{{ __('location.accuracy_label') }}"
        @if ($openOnError) data-open-on-load="1" @endif>
    <div class="box-border flex w-full flex-col overflow-visible rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/25">
        <div class="flex items-start justify-between gap-4 px-6 pb-4 pt-8">
            <div class="min-w-0 flex-1">
                <h2 id="location-modal-title" class="text-lg font-extrabold tracking-tight text-slate-900">
                    {{ __('location.location_settings') }}
                </h2>
                <p class="mt-1 text-sm leading-snug text-slate-500">{{ __('location.modal_sub_v4') }}</p>
                <p class="mt-2 rounded-xl bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700" data-location-active-banner></p>
            </div>
            <div class="cancel-btn-slot shrink-0 py-0">
                <x-ui.cancel-btn
                    type="button"
                    size="sm"
                    data-location-modal-close
                    :label="__('ui.close')"
                />
            </div>
        </div>

        <div class="mx-6 border-t border-slate-100"></div>

        <form method="POST" action="{{ route('settings.location.update') }}" class="flex flex-col gap-4 px-6 py-5" data-location-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="">
            <input type="hidden" id="location-modal-mode" value="nationwide">

            <div class="w-full">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="location-modal-distance">{{ __('location.discovery_distance') }}</label>
                <select id="location-modal-distance"
                        name="distance_km"
                        required
                        class="box-border w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    @foreach ($locationDistances as $distance)
                        <option value="{{ $distance }}" @selected((int) old('distance_km', $locationUser->distance_km ?? config('location.default_distance_km')) === (int) $distance)>
                            @if ((int) $distance === 0)
                                {{ __('location.all_distances') }}
                            @else
                                {{ $distance }} {{ __('location.km') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('distance_km')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div data-location-manual-panel>
                <input type="hidden" name="city_id" id="location-modal-city-id" value="{{ old('city_id', $locationUser->city_id) }}">

                <div class="w-full" data-city-search>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="location-modal-city-q">{{ __('location.search_location') }}</label>
                    <input id="location-modal-city-q"
                           type="search"
                           autocomplete="off"
                           value="{{ $profileCityLabel }}"
                           placeholder="{{ __('location.search_placeholder') }}"
                           class="box-border w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                           data-city-search-input>
                    <p class="mt-1 text-xs text-slate-500" data-city-selected-label>
                        {{ __('location.pick_from_results') }}
                    </p>
                    <ul class="mt-2 max-h-44 overflow-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm empty:hidden"
                        data-city-search-results
                        role="listbox"
                        hidden></ul>
                    @error('city_id')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div data-location-device-panel hidden class="rounded-xl border border-indigo-100 bg-indigo-50/70 px-3 py-3">
                <p class="text-sm font-semibold text-indigo-900">{{ __('location.current_location') }}</p>
                <p class="mt-1 text-xs text-indigo-800" data-location-accuracy hidden></p>
                <p class="mt-1 text-xs text-indigo-700">{{ __('location.device_hint') }}</p>
            </div>

            <p class="text-xs font-medium text-red-600" data-location-error hidden></p>

            <div class="grid grid-cols-1 gap-2">
                <button type="button"
                        data-location-use-device
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-800 transition hover:bg-indigo-100">
                    {{ __('location.detect_my_location') }}
                </button>
                <button type="button"
                        data-location-nationwide
                        class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                    {{ __('location.all_egypt') }}
                </button>
            </div>

            <div class="mt-1 grid w-full grid-cols-1 gap-2 sm:grid-cols-2 sm:items-center">
                <div class="cancel-btn-slot">
                    <x-ui.cancel-btn
                        type="button"
                        size="block"
                        data-location-modal-close
                        :label="__('ui.cancel')"
                    />
                </div>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/25 transition hover:brightness-105">
                    {{ __('location.save_short') }}
                </button>
            </div>
        </form>
    </div>
</dialog>
@endauth
