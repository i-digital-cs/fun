@extends('layouts.app')

@section('title', __('ui.forgot_password'))

@section('content')
    <x-auth.access-shell
        :action="route('password.email')"
        mode="form"
        digit-source="email"
        :title="__('ui.forgot_password')"
        :subtitle="__('ui.forgot_password_sub')"
        :enter-title="__('ui.access_enter_title')"
        :enter-sub="__('auth.reset_code_sending')"
        :verifying-title="__('ui.access_verifying_title')"
        :verifying-sub="__('auth.reset_code_sending')"
        :granted-title="__('auth.reset_code_sent_title')"
        :granted-sub="__('auth.reset_code_sent')"
    >
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('ui.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 font-extrabold text-white shadow-lg shadow-indigo-600/25 transition hover:brightness-105">
            {{ __('ui.send_reset_code') }}
        </button>

        <x-slot:footerForm>
            <p class="text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ __('ui.back_to_login') }}</a>
            </p>
        </x-slot:footerForm>
    </x-auth.access-shell>
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
@endpush
