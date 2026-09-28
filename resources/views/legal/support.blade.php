@extends('layouts.app')

@section('title', __('support.title'))

@section('content')
    <article class="mx-auto max-w-3xl space-y-6">
        <header class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ $brand }}</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('support.title') }}</h1>
            <p class="text-base leading-relaxed text-slate-600">{{ $intro }}</p>
        </header>

        <section class="rounded-3xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50 via-white to-orange-50/50 p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-indigo-500">{{ __('support.email_label') }}</p>
            <a href="mailto:{{ $email }}" class="mt-2 inline-block text-xl font-extrabold text-indigo-700 hover:underline">{{ $email }}</a>
        </section>

        @foreach ($sections as $section)
            <section class="space-y-2 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $section['title'] }}</h2>
                <p class="text-sm leading-relaxed text-slate-600">{{ $section['body'] }}</p>
            </section>
        @endforeach

        <div class="flex flex-wrap gap-3 text-sm font-semibold">
            <a href="{{ route('safety.guidelines') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700">{{ __('support.links.safety') }}</a>
            <a href="{{ route('legal.privacy') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('support.links.privacy') }}</a>
            <a href="{{ route('legal.terms') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('support.links.terms') }}</a>
            <a href="{{ route('legal.account-deletion') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 transition hover:border-rose-300 hover:text-rose-700">{{ __('support.links.deletion') }}</a>
        </div>
    </article>
@endsection
