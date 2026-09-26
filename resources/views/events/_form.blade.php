@php($event = $event ?? null)
@php($inputClass = 'w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100')
@php($labelClass = 'mb-1.5 block text-sm font-medium text-slate-700')
<div>
    <label class="{{ $labelClass }}" for="title">{{ __('events.title') }}</label>
    <input id="title" name="title" value="{{ old('title', $event?->title) }}" required class="{{ $inputClass }}">
</div>
<div>
    <label class="{{ $labelClass }}" for="description">{{ __('events.description') }}</label>
    <textarea id="description" name="description" rows="4" class="{{ $inputClass }}">{{ old('description', $event?->description) }}</textarea>
</div>
<div>
    <label class="{{ $labelClass }}" for="category_id">{{ __('events.category') }}</label>
    <select id="category_id" name="category_id" required class="{{ $inputClass }}">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $event?->category_id) == $category->id)>{{ $category->localizedName() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="{{ $labelClass }}" for="city_id">{{ __('events.city') }}</label>
    <select id="city_id" name="city_id" required class="{{ $inputClass }}">
        @foreach ($cities as $city)
            <option
                value="{{ $city->id }}"
                @selected(old('city_id', $event?->city_id) == $city->id)
                @if ($city->latitude !== null && $city->longitude !== null)
                    data-lat="{{ $city->latitude }}"
                    data-lng="{{ $city->longitude }}"
                @endif
            >{{ $city->localizedName() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="{{ $labelClass }}" for="venue">{{ __('events.venue') }}</label>
    <div class="relative" data-place-venue-wrap>
        <input
            id="venue"
            name="venue"
            value="{{ old('venue', $event?->venue) }}"
            class="{{ $inputClass }}"
            placeholder="{{ __('events.venue_placeholder') }}"
            data-place-venue
            autocomplete="off"
        >
        <div
            data-place-suggest
            hidden
            class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg fancy-scroll"
            role="listbox"
        ></div>
    </div>
    <p class="mt-1 text-xs text-slate-500">{{ __('events.venue_suggest_help') }}</p>
</div>
@php($placeLat = old('latitude', $event?->latitude))
@php($placeLng = old('longitude', $event?->longitude))
@php($selectedCity = $cities->firstWhere('id', (int) old('city_id', $event?->city_id)))
@php($defaultLat = ($placeLat !== null && $placeLat !== '') ? $placeLat : ($selectedCity?->latitude ?? 30.0444))
@php($defaultLng = ($placeLng !== null && $placeLng !== '') ? $placeLng : ($selectedCity?->longitude ?? 31.2357))
<div
    class="space-y-3 rounded-2xl border border-slate-200/70 bg-slate-50/60 p-4"
    data-event-place-picker
    data-default-lat="{{ $defaultLat }}"
    data-default-lng="{{ $defaultLng }}"
    data-status-pinned="{{ __('events.place_pinned') }}"
    data-status-from-link="{{ __('events.place_from_link') }}"
    data-status-from-suggest="{{ __('events.place_from_suggest') }}"
    data-status-cleared="{{ __('events.place_cleared') }}"
    data-status-resolving="{{ __('events.place_resolving') }}"
    data-status-unresolved="{{ __('events.maps_url_unresolved') }}"
>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <p class="text-sm font-semibold text-slate-800">{{ __('events.place_on_map') }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('events.place_on_map_help') }}</p>
        </div>
        <button type="button" data-place-clear class="text-xs font-medium text-slate-500 underline-offset-2 hover:text-indigo-600 hover:underline">
            {{ __('events.place_clear') }}
        </button>
    </div>
    <div>
        <label class="{{ $labelClass }}" for="maps_url">{{ __('events.maps_url') }}</label>
        <input
            id="maps_url"
            name="maps_url"
            type="url"
            value="{{ old('maps_url', $event?->maps_url) }}"
            class="{{ $inputClass }}"
            placeholder="{{ __('events.maps_url_placeholder') }}"
            data-place-url
            autocomplete="off"
        >
        <p class="mt-1 text-xs text-slate-500">{{ __('events.maps_url_help') }}</p>
    </div>
    <div data-place-map class="h-56 overflow-hidden rounded-xl border border-slate-200/80 bg-white sm:h-64"></div>
    <p class="text-xs font-medium text-indigo-600" data-place-status></p>
    <input type="hidden" name="latitude" value="{{ $placeLat }}" data-place-lat>
    <input type="hidden" name="longitude" value="{{ $placeLng }}" data-place-lng>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="{{ $labelClass }}" for="starts_at">{{ __('events.starts_at') }}</label>
        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($event?->starts_at)->format('Y-m-d\TH:i')) }}" required class="{{ $inputClass }}">
    </div>
    <div>
        <label class="{{ $labelClass }}" for="ends_at">{{ __('events.ends_at') }}</label>
        <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($event?->ends_at)->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}">
    </div>
</div>
<div>
    <label class="{{ $labelClass }}" for="max_participants">{{ __('events.max_participants') }}</label>
    <input id="max_participants" type="number" min="1" name="max_participants" value="{{ old('max_participants', $event?->max_participants ?? 10) }}" required class="{{ $inputClass }}">
</div>
<div>
    <label class="{{ $labelClass }}" for="sex">{{ __('events.sex') }}</label>
    <select id="sex" name="sex" required class="{{ $inputClass }}">
        <option value="" disabled @selected(! in_array(old('sex', $event?->sex?->value), ['male', 'female', 'mix'], true))>{{ __('ui.sex_required') }}</option>
        <option value="male" @selected(old('sex', $event?->sex?->value) === 'male')>{{ __('ui.sex_male') }}</option>
        <option value="female" @selected(old('sex', $event?->sex?->value) === 'female')>{{ __('ui.sex_female') }}</option>
        <option value="mix" @selected(old('sex', $event?->sex?->value ?? 'mix') === 'mix')>{{ __('ui.sex_mix') }}</option>
    </select>
    <p class="mt-1 text-xs text-slate-500">{{ __('events.sex_help') }}</p>
    @error('sex')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
<div>
    <label class="{{ $labelClass }}" for="requirements">{{ __('events.requirements') }}</label>
    <textarea id="requirements" name="requirements" rows="3" class="{{ $inputClass }}">{{ old('requirements', $event?->requirements) }}</textarea>
</div>
<div>
    <label class="{{ $labelClass }}" for="expected_cost">{{ __('events.expected_cost') }}</label>
    <input id="expected_cost" name="expected_cost" value="{{ old('expected_cost', $event?->expected_cost) }}" class="{{ $inputClass }}">
</div>
<div>
    <label class="{{ $labelClass }}" for="cost_per_person">{{ __('events.cost_per_person') }}</label>
    <input id="cost_per_person" type="number" step="0.01" min="0" name="cost_per_person" value="{{ old('cost_per_person', $event?->cost_per_person) }}" class="{{ $inputClass }}">
</div>
    <div>
        <x-ui.attachment-folder
            name="image"
            :label="__('events.image')"
            accept="image/jpeg,image/png,image/webp"
            :existing-url="$event?->imageUrl()"
            :existing-name="$event?->image_path ? basename($event->image_path) : null"
            :existing-tag="$event?->image_path ? 'IMG' : null"
            data-add-label="{{ __('ui.attachment_add') }}"
            data-drop-label="{{ __('ui.attachment_drop') }}"
        />
    </div>
