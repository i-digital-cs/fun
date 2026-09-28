@php
    $brand = $siteName ?? site_display_name();
    $locale = app()->getLocale();
@endphp

<footer class="mt-auto border-t border-border bg-surface py-4">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 sm:px-6 md:flex-row">
        <div class="flex items-center gap-2 text-sm">
            <span class="font-bold text-text">{{ $brand }}</span>
            <span class="text-muted" aria-hidden="true">•</span>
            <span class="text-muted">© {{ $brand }}</span>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-3 text-sm">
            <a href="{{ route('safety.guidelines') }}" class="inline-flex items-center gap-1 text-muted no-underline hover:text-text">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
                {{ __('home.safety_guidelines') }}
            </a>
            <a href="{{ route('legal.privacy') }}" class="inline-flex items-center gap-1 text-muted no-underline hover:text-text">
                {{ __('privacy.footer_link') }}
            </a>
            <a href="{{ route('legal.terms') }}" class="inline-flex items-center gap-1 text-muted no-underline hover:text-text">
                {{ __('terms.footer_link') }}
            </a>
            <a href="{{ route('legal.support') }}" class="inline-flex items-center gap-1 text-muted no-underline hover:text-text">
                {{ __('support.footer_link') }}
            </a>
            <a href="{{ route('safety.guidelines') }}" class="inline-flex items-center gap-1 text-muted no-underline hover:text-text">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 14.5l-3.5-3.5 1.4-1.4L11 12.7l4.6-4.6 1.4 1.4L11 15.5z"/>
                </svg>
                {{ __('home.trust_center') }}
            </a>
            <form method="POST" action="{{ route('locale.update') }}" class="inline-flex overflow-hidden rounded-lg border border-border text-xs" role="group" aria-label="{{ __('ui.language') }}">
                @csrf
                <button type="submit" name="locale" value="en"
                        @class([
                            'px-2.5 py-1.5 font-medium',
                            'bg-primary text-white' => $locale === 'en',
                            'bg-surface text-muted' => $locale !== 'en',
                        ])>
                    {{ __('ui.english') }}
                </button>
                <button type="submit" name="locale" value="ar"
                        @class([
                            'px-2.5 py-1.5 font-medium',
                            'bg-primary text-white' => $locale === 'ar',
                            'bg-surface text-muted' => $locale !== 'ar',
                        ])>
                    {{ __('ui.arabic') }}
                </button>
            </form>
        </div>
    </div>
</footer>
