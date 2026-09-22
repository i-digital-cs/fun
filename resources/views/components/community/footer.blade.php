@php
    $brand = site_display_name();
    $locale = app()->getLocale();
    $oppositeLocale = $locale === 'en' ? 'ar' : 'en';
    $oppositeLabel = $locale === 'en' ? __('ui.arabic') : __('ui.english');
@endphp

<footer class="mt-14 border-t border-slate-200/70 bg-white/60 backdrop-blur">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:flex-row sm:px-6">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
            </span>
            <span class="text-sm font-extrabold tracking-tight text-slate-900">{{ $brand }}</span>
            <span class="hidden items-center gap-1 text-xs font-medium text-slate-400 sm:flex">
                ·
                <svg class="h-3 w-3 fill-rose-400 text-rose-400" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
                {{ __('design.footer_tag') }}
            </span>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-slate-500">
            <a href="{{ route('safety.guidelines') }}" class="flex items-center gap-1 transition hover:text-emerald-600">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1.06 14.54l-3.48-3.48 1.41-1.41 2.07 2.07 4.24-4.24 1.41 1.41-5.65 5.65z"/>
                </svg>
                {{ __('home.safety_guidelines') }}
            </a>
            <a href="{{ route('safety.guidelines') }}" class="flex items-center gap-1 transition hover:text-indigo-600">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                    <path stroke-linecap="round" d="M4 22v-7"/>
                </svg>
                {{ __('home.trust_center') }}
            </a>
            <span class="text-slate-300">|</span>
            <form method="POST" action="{{ route('locale.update') }}" class="inline-flex">
                @csrf
                <input type="hidden" name="locale" value="{{ $oppositeLocale }}">
                <button type="submit"
                        class="flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 transition hover:border-indigo-300 hover:text-indigo-700"
                        aria-label="{{ __('ui.language') }}">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <path stroke-linecap="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                    {{ $oppositeLabel }}
                </button>
            </form>
        </div>
    </div>
    <p class="border-t border-slate-100 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 text-center text-[11px] text-slate-400">
        {{ __('design.rights', ['year' => now()->year, 'brand' => $brand]) }}
    </p>
</footer>
