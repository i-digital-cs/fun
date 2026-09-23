@extends('layouts.admin')

@section('title', __('admin.site_settings'))

@section('content')

    <h1 class="text-2xl font-semibold">{{ __('admin.site_settings') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('admin.site_settings_help') }}</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-6 max-w-lg space-y-5 rounded-2xl border border-border bg-surface p-6">
        @csrf
        @method('PUT')
        <div>
            <label for="display_name" class="mb-1 block text-sm font-medium">{{ __('admin.display_name') }}</label>
            <input
                id="display_name"
                name="display_name"
                type="text"
                maxlength="80"
                required
                value="{{ old('display_name', $settings->display_name) }}"
                class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm"
            >
        </div>

        <div>
            <label for="logo" class="mb-1 block text-sm font-medium">{{ __('admin.logo') }}</label>
            <p class="mb-2 text-xs text-muted">{{ __('admin.logo_help') }}</p>
            @if ($logoUrl)
                <div class="mb-3 flex items-center gap-3 rounded-xl border border-border bg-background p-3">
                    <img src="{{ $logoUrl }}" alt="{{ $settings->display_name }}" class="h-12 max-w-[8rem] object-contain">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('admin.remove_logo') }}
                    </label>
                </div>
            @endif
            <input
                id="logo"
                name="logo"
                type="file"
                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700"
            >
        </div>

        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('admin.save_settings') }}</button>
    </form>
@endsection
