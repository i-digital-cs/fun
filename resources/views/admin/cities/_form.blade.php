@php($city = $city ?? null)
<div>
    <label class="mb-1 block text-sm font-medium" for="name_en">{{ __('location.name_en') }}</label>
    <input id="name_en" name="name_en" value="{{ old('name_en', $city?->name_en) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="name_ar">{{ __('location.name_ar') }}</label>
    <input id="name_ar" name="name_ar" value="{{ old('name_ar', $city?->name_ar) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="latitude">{{ __('location.latitude') }}</label>
    <input id="latitude" name="latitude" type="number" step="any" value="{{ old('latitude', $city?->latitude) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="longitude">{{ __('location.longitude') }}</label>
    <input id="longitude" name="longitude" type="number" step="any" value="{{ old('longitude', $city?->longitude) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
