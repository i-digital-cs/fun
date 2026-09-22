@extends('layouts.admin')

@section('title', __('admin.site_settings'))

@section('content')

    <h1 class="text-2xl font-semibold">{{ __('admin.site_settings') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('admin.site_settings_help') }}</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6 max-w-lg space-y-4 rounded-2xl border border-border bg-surface p-6">
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
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('admin.save_settings') }}</button>
    </form>
@endsection
