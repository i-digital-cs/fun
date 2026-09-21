@extends('layouts.app')

@section('title', __('ui.login'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-text">{{ __('ui.login') }}</h1>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium" for="email">{{ __('ui.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-border px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="password">{{ __('ui.password') }}</label>
                <input id="password" type="password" name="password" required class="w-full rounded-lg border border-border px-3 py-2">
            </div>
            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" value="1">
                {{ __('ui.remember') }}
            </label>
            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-2.5 font-semibold text-white">{{ __('ui.login') }}</button>
        </form>
        <a href="{{ route('auth.google.redirect') }}" class="mt-4 flex w-full items-center justify-center rounded-lg border border-border px-4 py-2.5 text-sm font-medium">
            {{ __('ui.continue_google') }}
        </a>
    </div>
@endsection
