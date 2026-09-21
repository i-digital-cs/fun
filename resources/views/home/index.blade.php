@extends('layouts.app')

@section('title', $appName)

@section('content')
    <section class="flex min-h-[60vh] flex-col justify-center py-8">
        <p class="mb-3 text-sm font-medium uppercase tracking-wide text-primary">
            {{ $appName }}
        </p>
        <h1 class="max-w-2xl text-4xl font-semibold tracking-tight text-text sm:text-5xl">
            {{ __('home.headline') }}
        </h1>
        <p class="mt-4 max-w-xl text-lg text-muted">
            {{ __('home.subheadline') }}
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            @guest
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-white">
                    {{ __('ui.register') }}
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg border border-border bg-surface px-5 py-2.5 text-sm font-medium text-text">
                    {{ __('ui.login') }}
                </a>
            @else
                @if (auth()->user()->needsIdentityDocuments())
                    <a href="{{ route('identity.create') }}" class="inline-flex items-center rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-white">
                        {{ __('identity.title') }}
                    </a>
                @else
                    <a href="{{ route('events.index') }}" class="inline-flex items-center rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-white">
                        {{ __('events.discover') }}
                    </a>
                @endif
            @endguest
        </div>
        <p class="mt-10 max-w-lg text-sm text-muted">
            {{ __('identity.help') }}
        </p>
    </section>
@endsection
