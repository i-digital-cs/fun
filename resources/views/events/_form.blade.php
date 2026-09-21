@php($event = $event ?? null)
<div>
    <label class="mb-1 block text-sm font-medium" for="title">{{ __('events.title') }}</label>
    <input id="title" name="title" value="{{ old('title', $event?->title) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="description">{{ __('events.description') }}</label>
    <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-border px-3 py-2">{{ old('description', $event?->description) }}</textarea>
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="category_id">{{ __('events.category') }}</label>
    <select id="category_id" name="category_id" required class="w-full rounded-lg border border-border px-3 py-2">
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $event?->category_id) == $category->id)>{{ $category->localizedName() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="city_id">{{ __('events.city') }}</label>
    <select id="city_id" name="city_id" required class="w-full rounded-lg border border-border px-3 py-2">
        @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected(old('city_id', $event?->city_id) == $city->id)>{{ $city->localizedName() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="venue">{{ __('events.venue') }}</label>
    <input id="venue" name="venue" value="{{ old('venue', $event?->venue) }}" class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-sm font-medium" for="starts_at">{{ __('events.starts_at') }}</label>
        <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($event?->starts_at)->format('Y-m-d\TH:i')) }}" required class="w-full rounded-lg border border-border px-3 py-2">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium" for="ends_at">{{ __('events.ends_at') }}</label>
        <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($event?->ends_at)->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-border px-3 py-2">
    </div>
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="max_participants">{{ __('events.max_participants') }}</label>
    <input id="max_participants" type="number" min="1" name="max_participants" value="{{ old('max_participants', $event?->max_participants ?? 10) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="requirements">{{ __('events.requirements') }}</label>
    <textarea id="requirements" name="requirements" rows="3" class="w-full rounded-lg border border-border px-3 py-2">{{ old('requirements', $event?->requirements) }}</textarea>
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="expected_cost">{{ __('events.expected_cost') }}</label>
    <input id="expected_cost" name="expected_cost" value="{{ old('expected_cost', $event?->expected_cost) }}" class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="cost_per_person">{{ __('events.cost_per_person') }}</label>
    <input id="cost_per_person" type="number" step="0.01" min="0" name="cost_per_person" value="{{ old('cost_per_person', $event?->cost_per_person) }}" class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="image">{{ __('events.image') }}</label>
    <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-dashed border-border px-3 py-3 text-sm">
</div>
