@extends('layouts.admin')

@section('title', __('admin.site_settings'))

@section('content')

    <h1 class="text-2xl font-semibold">{{ __('admin.site_settings') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('admin.site_settings_help') }}</p>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-6 max-w-2xl space-y-6 rounded-2xl border border-border bg-surface p-6">
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
            <label class="mb-1 block text-sm font-medium">{{ __('admin.logo') }}</label>
            <p class="mb-3 text-xs text-muted">{{ __('admin.logo_help') }}</p>
            <div class="flex flex-wrap items-start gap-6">
                <x-ui.attachment-folder
                    name="logo"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    :existing-url="$logoUrl"
                    :existing-name="$logoUrl ? 'logo' : null"
                    existing-tag="LOGO"
                    data-add-label="{{ __('ui.attachment_add') }}"
                    data-drop-label="{{ __('ui.attachment_drop') }}"
                />
                @if ($logoUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        {{ __('admin.remove_logo') }}
                    </label>
                @endif
            </div>
        </div>

        <div class="border-t border-border pt-5">
            <h2 class="text-base font-semibold text-slate-900">{{ __('admin.app_version_section') }}</h2>
            <p class="mt-1 text-xs text-muted">{{ __('admin.app_version_help') }}</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="android_latest_version" class="mb-1 block text-sm font-medium">{{ __('admin.android_latest_version') }}</label>
                    <input id="android_latest_version" name="android_latest_version" type="text" required
                           value="{{ old('android_latest_version', $settings->android_latest_version ?? '1.0.0') }}"
                           class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="1.2.0">
                </div>
                <div>
                    <label for="android_min_version" class="mb-1 block text-sm font-medium">{{ __('admin.android_min_version') }}</label>
                    <input id="android_min_version" name="android_min_version" type="text" required
                           value="{{ old('android_min_version', $settings->android_min_version ?? '1.0.0') }}"
                           class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="1.0.0">
                </div>
                <div>
                    <label for="ios_latest_version" class="mb-1 block text-sm font-medium">{{ __('admin.ios_latest_version') }}</label>
                    <input id="ios_latest_version" name="ios_latest_version" type="text" required
                           value="{{ old('ios_latest_version', $settings->ios_latest_version ?? '1.0.0') }}"
                           class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="1.2.0">
                </div>
                <div>
                    <label for="ios_min_version" class="mb-1 block text-sm font-medium">{{ __('admin.ios_min_version') }}</label>
                    <input id="ios_min_version" name="ios_min_version" type="text" required
                           value="{{ old('ios_min_version', $settings->ios_min_version ?? '1.0.0') }}"
                           class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="1.0.0">
                </div>
                <div class="sm:col-span-2">
                    <label for="android_store_url" class="mb-1 block text-sm font-medium">{{ __('admin.android_store_url') }}</label>
                    <input id="android_store_url" name="android_store_url" type="url"
                           value="{{ old('android_store_url', $settings->android_store_url) }}"
                           class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="https://play.google.com/store/apps/details?id=...">
                </div>
                <div class="sm:col-span-2">
                    <label for="ios_store_url" class="mb-1 block text-sm font-medium">{{ __('admin.ios_store_url') }}</label>
                    <input id="ios_store_url" name="ios_store_url" type="url"
                           value="{{ old('ios_store_url', $settings->ios_store_url) }}"
                           class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm" placeholder="https://apps.apple.com/app/...">
                </div>
                <div>
                    <label for="update_message_en" class="mb-1 block text-sm font-medium">{{ __('admin.update_message_en') }}</label>
                    <textarea id="update_message_en" name="update_message_en" rows="2" maxlength="500"
                              class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">{{ old('update_message_en', $settings->update_message_en) }}</textarea>
                </div>
                <div>
                    <label for="update_message_ar" class="mb-1 block text-sm font-medium">{{ __('admin.update_message_ar') }}</label>
                    <textarea id="update_message_ar" name="update_message_ar" rows="2" maxlength="500"
                              class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">{{ old('update_message_ar', $settings->update_message_ar) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <input type="hidden" name="force_update" value="0">
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="force_update" value="1" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               @checked(old('force_update', $settings->force_update))>
                        <span>
                            <span class="font-medium">{{ __('admin.force_update') }}</span>
                            <span class="mt-0.5 block text-xs text-muted">{{ __('admin.force_update_help') }}</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('admin.save_settings') }}</button>
    </form>
@endsection
