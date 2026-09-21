@php($category = $category ?? null)
<div>
    <label class="mb-1 block text-sm font-medium" for="name_en">{{ __('catalog.name_en') }}</label>
    <input id="name_en" name="name_en" value="{{ old('name_en', $category?->name_en) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="name_ar">{{ __('catalog.name_ar') }}</label>
    <input id="name_ar" name="name_ar" value="{{ old('name_ar', $category?->name_ar) }}" required class="w-full rounded-lg border border-border px-3 py-2">
</div>
<div>
    <label class="mb-1 block text-sm font-medium" for="sort_order">{{ __('catalog.sort_order') }}</label>
    <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}" class="w-full rounded-lg border border-border px-3 py-2">
</div>
