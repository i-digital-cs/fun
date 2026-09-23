@auth
@php
    $locationUser = auth()->user()->loadMissing('city');
    $locationCities = \App\Models\City::query()->active()->orderBy('name_en')->get(['id', 'name_en', 'name_ar']);
    $locationDistances = config('location.distances_km', [5, 10, 25, 50]);
    $openOnError = $errors->has('city_id') || $errors->has('distance_km');
@endphp

<dialog id="location-modal"
        class="location-modal fixed inset-0 m-auto h-fit max-h-[min(90vh,40rem)] w-[min(100%-2rem,24rem)] overflow-visible rounded-3xl border-0 bg-transparent p-0 shadow-none outline-none backdrop:bg-slate-950/50"
        aria-labelledby="location-modal-title"
        @if ($openOnError) data-open-on-load="1" @endif>
    <div class="box-border flex w-full flex-col overflow-visible rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/25">
        <div class="flex items-start justify-between gap-4 px-6 pb-4 pt-8">
            <div class="min-w-0 flex-1">
                <h2 id="location-modal-title" class="text-lg font-extrabold tracking-tight text-slate-900">
                    {{ __('location.location_settings') }}
                </h2>
                <p class="mt-1 text-sm leading-snug text-slate-500">{{ __('location.modal_sub') }}</p>
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

        <form method="POST" action="{{ route('settings.location.update') }}" class="flex flex-col gap-4 px-6 py-5">
            @csrf
            @method('PUT')

            <div class="w-full">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="location-modal-city">{{ __('location.your_city') }}</label>
                <select id="location-modal-city"
                        name="city_id"
                        required
                        class="box-border w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    <option value="">—</option>
                    @foreach ($locationCities as $city)
                        <option value="{{ $city->id }}" @selected((int) old('city_id', $locationUser->city_id) === (int) $city->id)>
                            {{ $city->localizedName() }}
                        </option>
                    @endforeach
                </select>
                @error('city_id')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="location-modal-distance">{{ __('location.discovery_distance') }}</label>
                <select id="location-modal-distance"
                        name="distance_km"
                        required
                        class="box-border w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    @foreach ($locationDistances as $distance)
                        <option value="{{ $distance }}" @selected((int) old('distance_km', $locationUser->distance_km ?? config('location.default_distance_km')) === (int) $distance)>
                            {{ $distance }} {{ __('location.km') }}
                        </option>
                    @endforeach
                </select>
                @error('distance_km')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
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

<script>
(() => {
    const modal = document.getElementById('location-modal');
    if (!modal) return;

    const open = () => {
        if (typeof modal.showModal === 'function') modal.showModal();
    };
    const close = () => {
        if (typeof modal.close === 'function') modal.close();
    };

    document.querySelectorAll('[data-location-modal-open]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            const details = el.closest('details');
            if (details) details.open = false;
            open();
        });
    });

    modal.querySelectorAll('[data-location-modal-close]').forEach((el) => {
        el.addEventListener('click', close);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    if (modal.dataset.openOnLoad === '1') open();
})();
</script>
@endauth
