@extends('layouts.app')

@section('title', __('ui.register'))

@section('content')
    <x-auth.access-shell
        :action="route('register')"
        mode="form"
        digit-source="password"
        :title="__('ui.register')"
        :subtitle="__('ui.register_sub')"
        :enter-title="__('ui.access_enter_title')"
        :enter-sub="__('ui.access_enter_sub')"
        :granted-title="__('ui.access_registered_title')"
        :granted-sub="__('ui.access_registered_sub')"
        :show-google="true"
    >
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="name">{{ __('ui.name') }}</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('ui.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
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
        <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
            {{ __('ui.register') }}
        </button>

        <x-slot:footerForm>
            <p class="text-center text-sm text-slate-500">
                {{ __('ui.have_account') }}
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('ui.login') }}</a>
            </p>
        </x-slot:footerForm>
    </x-auth.access-shell>
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
@endpush
