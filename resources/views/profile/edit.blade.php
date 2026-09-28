@extends('layouts.dashboard')

@section('title', __('profile.title'))

@section('content')
    @php
        $user = $user ?? auth()->user();
        $photo = filled($user->profile_image_path) ? $user->profile_image_path : null;
        $initials = user_initials($user->name);
        $hasPassword = filled($user->getAuthPassword());
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('profile.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('profile.subtitle') }}</p>
        </div>

        @if ($user->public_id)
            <section class="mb-6 rounded-3xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50 via-white to-orange-50/60 p-5 shadow-sm sm:p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-500">{{ __('profile.your_public_id') }}</p>
                <p class="mt-2 font-mono text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ $user->public_id }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('profile.public_id_help') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('users.public.show', $user->public_id) }}"
                       class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        {{ __('profile.copy_profile_link') }}
                    </a>
                    <a href="{{ route('friends.index') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                        {{ __('friends.title') }}
                    </a>
                    <a href="{{ route('settings.notifications.edit') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
                        {{ __('friends.notify_friend_events') }}
                    </a>
                </div>
            </section>
        @endif

        <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('profile.photo_section') }}</h2>
                <div class="mt-4 flex flex-wrap items-start gap-6">
                    <x-ui.attachment-folder
                        name="photo"
                        accept="image/png,image/jpeg,image/webp"
                        :existing-url="$photo"
                        :existing-name="$photo ? 'profile.jpg' : null"
                        existing-tag="IMG"
                        data-add-label="{{ __('ui.attachment_add') }}"
                        data-drop-label="{{ __('ui.attachment_drop') }}"
                    />
                    <div class="min-w-0 flex-1 space-y-2 pt-2">
                        <p class="text-xs text-slate-400">{{ __('profile.photo_help') }}</p>
                        @if ($photo)
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                {{ __('profile.remove_photo') }}
                            </label>
                        @endif
                        @error('photo')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('profile.account_section') }}</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="name">{{ __('ui.name') }}</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('ui.email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                        <p class="mt-1 text-xs text-slate-400">{{ __('profile.email_help') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="sex">{{ __('ui.sex') }}</label>
                        <select id="sex" name="sex" required
                                class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            <option value="" disabled @selected(old('sex', $user->sex?->value) === null)>{{ __('ui.sex_required') }}</option>
                            <option value="male" @selected(old('sex', $user->sex?->value) === 'male')>{{ __('ui.sex_male') }}</option>
                            <option value="female" @selected(old('sex', $user->sex?->value) === 'female')>{{ __('ui.sex_female') }}</option>
                            <option value="mix" @selected(old('sex', $user->sex?->value) === 'mix')>{{ __('ui.sex_mix') }}</option>
                        </select>
                        @error('sex')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="locale">{{ __('ui.language') }}</label>
                        <select id="locale" name="locale" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                            <option value="en" @selected(old('locale', $user->locale) === 'en')>{{ __('ui.english') }}</option>
                            <option value="ar" @selected(old('locale', $user->locale) === 'ar')>{{ __('ui.arabic') }}</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('profile.password_section') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $hasPassword ? __('profile.password_help') : __('profile.password_set_help') }}</p>
                <div class="mt-4 space-y-4">
                    @if ($hasPassword)
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="current_password">{{ __('profile.current_password') }}</label>
                            <input id="current_password" type="password" name="current_password" autocomplete="current-password"
                                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                        </div>
                    @endif
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('profile.new_password') }}</label>
                        <input id="password" type="password" name="password" autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password_confirmation">{{ __('ui.password_confirmation') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-indigo-600/25 transition hover:brightness-105">
                    {{ __('profile.save') }}
                </button>
                <a href="{{ route('settings.location.edit') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 no-underline transition hover:border-indigo-300 hover:text-indigo-700">
                    {{ __('dashboard.nav_location') }}
                </a>
            </div>
        </form>

        <section class="mt-8 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">{{ __('privacy.footer_link') }} · {{ __('terms.footer_link') }}</h2>
            <div class="mt-4 flex flex-wrap gap-3 text-sm font-semibold">
                <a href="{{ route('legal.privacy') }}" class="text-indigo-600 hover:underline">{{ __('privacy.footer_link') }}</a>
                <a href="{{ route('legal.terms') }}" class="text-indigo-600 hover:underline">{{ __('terms.footer_link') }}</a>
                <a href="{{ route('legal.support') }}" class="text-indigo-600 hover:underline">{{ __('support.footer_link') }}</a>
                <a href="{{ route('settings.account.delete') }}" class="text-rose-600 hover:underline">{{ __('account.delete_title') }}</a>
            </div>
        </section>
    </div>
@endsection
