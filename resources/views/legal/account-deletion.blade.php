@extends('layouts.app')

@section('title', __('account_deletion.title'))

@section('content')
    <article class="mx-auto max-w-3xl space-y-6">
        <header class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">{{ $brand }}</p>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('account_deletion.title') }}</h1>
            <p class="text-base leading-relaxed text-slate-600">{{ $intro }}</p>
        </header>

        <section class="space-y-3 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('account_deletion.steps_title') }}</h2>
            <ol class="list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-600">
                @foreach ($steps as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>
            @auth
                <a href="{{ route('settings.account.delete') }}"
                   class="mt-4 inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-rose-500">
                    {{ __('account_deletion.footer_link') }}
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="mt-4 inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-indigo-500">
                    {{ __('ui.login') }}
                </a>
            @endauth
        </section>

        <section class="space-y-2 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('account_deletion.cancel_title') }}</h2>
            <p class="text-sm leading-relaxed text-slate-600">{{ $cancelBody }}</p>
        </section>

        <section class="space-y-2 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ __('account_deletion.help_title') }}</h2>
            <p class="text-sm leading-relaxed text-slate-600">{{ $helpBody }}</p>
            <div class="mt-3 flex flex-wrap gap-3 text-sm font-semibold">
                <a href="mailto:{{ $email }}" class="text-indigo-600 hover:underline">{{ $email }}</a>
                <a href="{{ route('legal.support') }}" class="text-indigo-600 hover:underline">{{ __('support.footer_link') }}</a>
                <a href="{{ route('legal.privacy') }}" class="text-indigo-600 hover:underline">{{ __('privacy.footer_link') }}</a>
            </div>
        </section>
    </article>
@endsection
