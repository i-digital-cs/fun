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
            <option value="{{ $city->id }}" @selected(old('city_id', $event?->city_id) == $city->id)>{{ $city->localizedName() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="{{ $labelClass }}" for="venue">{{ __('events.venue') }}</label>
    <input id="venue" name="venue" value="{{ old('venue', $event?->venue) }}" class="{{ $inputClass }}">
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
