@extends('layouts.app')

@section('title', __('ui.reset_password'))

@section('content')
    <x-auth.access-shell
        :action="route('password.update')"
        mode="form"
        digit-source="password"
        :title="__('ui.reset_password')"
        :subtitle="__('ui.reset_password_sub')"
        :granted-title="__('ui.access_granted_title')"
        :granted-sub="__('auth.password_reset')"
    >
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('ui.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password_confirmation">{{ __('ui.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 font-extrabold text-white shadow-lg shadow-indigo-600/25 transition hover:brightness-105">
            {{ __('ui.reset_password') }}
        </button>
    </x-auth.access-shell>
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
@endpush
