@extends('layouts.app')

@section('title', __('safety.guidelines_title'))

@section('content')
    <article class="mx-auto max-w-3xl space-y-6">
        <header class="space-y-2">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ site_display_name() }}</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('safety.guidelines_title') }}</h1>
            <p class="text-slate-500">{{ __('safety.guidelines_intro') }}</p>
        </header>

        <section class="space-y-2 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('safety.guideline_identity_title') }}</h2>
            <p class="text-sm text-slate-600">{{ __('identity.help') }}</p>
        </section>

        <section class="space-y-2 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('safety.guideline_report_title') }}</h2>
            <p class="text-sm text-slate-600">{{ __('safety.guideline_report_body') }}</p>
            @auth
                @if (auth()->user()->canParticipate())
                    <a href="{{ route('reports.create') }}" class="mt-3 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">{{ __('safety.report') }}</a>
                @endif
            @endauth
        </section>

        <section class="space-y-2 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('safety.guideline_block_title') }}</h2>
            <p class="text-sm text-slate-600">{{ __('safety.guideline_block_body') }}</p>
            @auth
                @if (auth()->user()->canParticipate())
                    <a href="{{ route('blocks.index') }}" class="mt-3 inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('safety.blocked_users') }}</a>
                @endif
            @endauth
        </section>

        <p class="text-sm text-slate-500">{{ __('safety.guidelines_disclaimer') }}</p>
    </article>
@endsection
