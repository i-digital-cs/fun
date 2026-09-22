@props([
    'user' => null,
])

@php
    $canReport = $user?->canParticipate() === true;
@endphp

<section class="mb-4" aria-labelledby="home-trust-heading">
    <div class="flex flex-col gap-4 rounded-2xl border border-border bg-surface p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div class="flex max-w-3xl gap-4">
            <div class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success" aria-hidden="true">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="home-trust-heading" class="text-base font-semibold text-text sm:text-lg">
                        {{ __('home.trust_title') }}
                    </h2>
                    <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-[11px] font-semibold text-success">
                        {{ __('home.safer_community') }}
                    </span>
                </div>
                <p class="text-sm text-muted sm:text-base">
                    {{ __('identity.help') }}
                    {{ __('home.trust_body') }}
                </p>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
            @if ($canReport)
                <a href="{{ route('reports.create') }}" class="inline-flex items-center gap-1 rounded-lg border border-border bg-background px-4 py-2 text-sm font-medium text-text hover:border-primary/40">
                    <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                        <path stroke-linecap="round" d="M4 22v-7"/>
                    </svg>
                    {{ __('home.report_issue') }}
                </a>
            @elseif ($user === null)
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1 rounded-lg border border-border bg-background px-4 py-2 text-sm font-medium text-text hover:border-primary/40">
                    {{ __('home.report_issue') }}
                </a>
            @endif

            <a href="{{ route('safety.guidelines') }}" class="inline-flex items-center gap-1 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                {{ __('home.safety_guidelines') }}
            </a>
        </div>
    </div>
</section>
