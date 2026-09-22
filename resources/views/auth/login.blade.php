@extends('layouts.app')

@section('title', __('ui.login'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-xl shadow-indigo-600/5 backdrop-blur sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('ui.login') }}</h1>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('ui.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('ui.password') }}</label>
                <input id="password" type="password" name="password" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-500">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                {{ __('ui.remember') }}
            </label>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 font-extrabold text-white shadow-lg shadow-indigo-600/25 transition hover:brightness-105">{{ __('ui.login') }}</button>
        </form>
        <a href="{{ route('auth.google.redirect') }}" class="mt-4 flex w-full items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">
            {{ __('ui.continue_google') }}
        </a>
    </div>
@endsection
