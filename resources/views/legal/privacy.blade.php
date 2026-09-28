@extends('layouts.app')

@section('title', __('privacy.title'))

@section('content')
    <article class="mx-auto max-w-3xl space-y-6">
        <header class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ $brand }}</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('privacy.title') }}</h1>
            <p class="text-sm font-medium text-slate-500">
                {{ __('privacy.updated', ['date' => __('privacy.updated_date')]) }}
            </p>
            <p class="text-base leading-relaxed text-slate-600">{{ $intro }}</p>
        </header>

        <nav class="rounded-3xl border border-slate-200/70 bg-white/80 p-5 shadow-sm backdrop-blur" aria-label="{{ __('privacy.title') }}">
            <ol class="grid gap-2 text-sm font-semibold text-indigo-700 sm:grid-cols-2">
                @foreach ($sections as $index => $section)
                    <li>
                        <a href="#privacy-section-{{ $index + 1 }}" class="transition hover:text-indigo-900 hover:underline">
                            {{ $section['title'] }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>

        @foreach ($sections as $index => $section)
            <section
                id="privacy-section-{{ $index + 1 }}"
                class="scroll-mt-28 space-y-3 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-7"
            >
                <h2 class="text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">{{ $section['title'] }}</h2>
                @foreach ($section['paragraphs'] as $paragraph)
                    <p class="text-sm leading-relaxed whitespace-pre-line text-slate-600">{{ $paragraph }}</p>
                @endforeach
            </section>
        @endforeach

        <p class="text-sm text-slate-500">
            <a href="mailto:{{ $email }}" class="font-semibold text-indigo-600 hover:underline">{{ $email }}</a>
            ·
            <a href="{{ route('safety.guidelines') }}" class="font-semibold text-emerald-700 hover:underline">{{ __('home.safety_guidelines') }}</a>
        </p>
    </article>
@endsection
