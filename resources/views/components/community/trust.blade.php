@php
    $user = auth()->user();
    $canReport = $user?->canParticipate() === true;
@endphp

<section class="relative mt-12 overflow-hidden rounded-[1.35rem] bg-gradient-to-r from-[#0F7357] via-[#159B76] to-[#0D9488] p-6 text-white shadow-[0_22px_50px_-24px_rgba(21,155,118,0.55)] sm:rounded-[2rem] sm:p-8">
    <div aria-hidden="true" class="absolute inset-0">
        <div class="absolute -end-16 -top-16 h-64 w-64 rounded-full bg-white/15 blur-3xl"></div>
        <div class="absolute -start-10 bottom-0 h-40 w-40 rounded-full bg-emerald-200/20 blur-3xl"></div>
        <svg class="animate-float absolute end-8 top-1/2 hidden h-40 w-40 -translate-y-1/2 text-white/10 lg:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1.06 14.54l-3.48-3.48 1.41-1.41 2.07 2.07 4.24-4.24 1.41 1.41-5.65 5.65z"/>
        </svg>
        <div class="bg-dots absolute inset-0 opacity-35"></div>
    </div>

    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border border-white/25 bg-white/15 shadow-lg backdrop-blur-xl">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
            </span>
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-lg font-extrabold tracking-tight sm:text-xl">{{ __('design.trust_title') }}</h2>
                    <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold backdrop-blur">
                        {{ __('design.trust_members') }}
                    </span>
                </div>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-emerald-50/90">
                    {{ __('design.trust_body') }}
                </p>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2.5">
            @if ($canReport)
                <a href="{{ route('reports.create') }}" class="flex items-center gap-1.5 rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-xs font-bold backdrop-blur transition hover:bg-white/20">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                        <path stroke-linecap="round" d="M4 22v-7"/>
                    </svg>
                    {{ __('home.report_issue') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="flex items-center gap-1.5 rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-xs font-bold backdrop-blur transition hover:bg-white/20">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                        <path stroke-linecap="round" d="M4 22v-7"/>
                    </svg>
                    {{ __('home.report_issue') }}
                </a>
            @endif
            <a href="{{ route('safety.guidelines') }}" class="flex items-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-xs font-extrabold text-emerald-700 shadow-lg transition hover:scale-[1.03] active:scale-95">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                {{ __('home.safety_guidelines') }}
            </a>
        </div>
    </div>
</section>
