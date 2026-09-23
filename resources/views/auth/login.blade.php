@extends('layouts.app')

@section('title', __('ui.login'))

@section('content')
    <x-auth.access-shell
        :action="route('login')"
        mode="form"
        digit-source="password"
        :title="__('ui.login')"
        :subtitle="__('ui.access_form_sub')"
        :show-google="true"
    >
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('ui.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('ui.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-500">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            {{ __('ui.remember') }}
        </label>
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 font-extrabold text-white shadow-lg shadow-indigo-600/25 transition hover:brightness-105">
            {{ __('ui.login') }}
        </button>

        <x-slot:footerForm>
            <p class="text-center text-sm text-slate-500">
                <a href="{{ route('password.request') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('ui.forgot_password') }}</a>
            </p>
            <p class="text-center text-sm text-slate-500">
                {{ __('ui.no_account') }}
                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('ui.register') }}</a>
            </p>
        </x-slot:footerForm>
    </x-auth.access-shell>
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
@endpush
