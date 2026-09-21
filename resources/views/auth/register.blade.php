@extends('layouts.app')

@section('title', __('ui.register'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-text">{{ __('ui.register') }}</h1>
        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium" for="name">{{ __('ui.name') }}</label>
                <input id="name" name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-border px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="email">{{ __('ui.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-border px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="password">{{ __('ui.password') }}</label>
                <input id="password" type="password" name="password" required class="w-full rounded-lg border border-border px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="password_confirmation">{{ __('ui.password_confirmation') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-lg border border-border px-3 py-2">
            </div>
            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
            <button type="submit" class="w-full rounded-lg bg-accent px-4 py-2.5 font-semibold text-white">{{ __('ui.register') }}</button>
        </form>
        <a href="{{ route('auth.google.redirect') }}" class="mt-4 flex w-full items-center justify-center rounded-lg border border-border px-4 py-2.5 text-sm font-medium">
            {{ __('ui.continue_google') }}
        </a>
    </div>
@endsection
