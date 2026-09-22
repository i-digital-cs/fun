@extends('layouts.app')

@section('title', __('ui.register'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-xl shadow-indigo-600/5 backdrop-blur sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('ui.register') }}</h1>
        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="name">{{ __('ui.name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('ui.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('ui.password') }}</label>
                <input id="password" type="password" name="password" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password_confirmation">{{ __('ui.password_confirmation') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">{{ __('ui.register') }}</button>
        </form>
        <a href="{{ route('auth.google.redirect') }}" class="mt-4 flex w-full items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
            {{ __('ui.continue_google') }}
        </a>
    </div>
@endsection
