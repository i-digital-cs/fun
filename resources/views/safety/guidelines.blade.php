@extends('layouts.app')

@section('title', __('safety.guidelines_title'))

@section('content')
    <article class="mx-auto max-w-3xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm font-medium uppercase tracking-wide text-primary">{{ site_display_name() }}</p>
            <h1 class="text-3xl font-semibold tracking-tight text-text">{{ __('safety.guidelines_title') }}</h1>
            <p class="text-muted">{{ __('safety.guidelines_intro') }}</p>
        </header>

        <section class="space-y-2 rounded-2xl border border-border bg-surface p-5">
            <h2 class="text-lg font-semibold text-text">{{ __('safety.guideline_identity_title') }}</h2>
            <p class="text-sm text-muted">{{ __('identity.help') }}</p>
        </section>

        <section class="space-y-2 rounded-2xl border border-border bg-surface p-5">
            <h2 class="text-lg font-semibold text-text">{{ __('safety.guideline_report_title') }}</h2>
            <p class="text-sm text-muted">{{ __('safety.guideline_report_body') }}</p>
            @auth
                @if (auth()->user()->canParticipate())
                    <a href="{{ route('reports.create') }}" class="mt-2 inline-flex rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('safety.report') }}</a>
                @endif
            @endauth
        </section>

        <section class="space-y-2 rounded-2xl border border-border bg-surface p-5">
            <h2 class="text-lg font-semibold text-text">{{ __('safety.guideline_block_title') }}</h2>
            <p class="text-sm text-muted">{{ __('safety.guideline_block_body') }}</p>
            @auth
                @if (auth()->user()->canParticipate())
                    <a href="{{ route('blocks.index') }}" class="mt-2 inline-flex rounded-lg border border-border px-4 py-2 text-sm font-medium">{{ __('safety.blocked_users') }}</a>
                @endif
            @endauth
        </section>

        <p class="text-sm text-muted">{{ __('safety.guidelines_disclaimer') }}</p>
    </article>
@endsection
