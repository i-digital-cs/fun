@props([
    'appName',
    'user' => null,
    'when' => null,
])

@php
    $isAuth = $user !== null;
    $cityName = $user?->city?->localizedName();
    $isActive = $user?->canParticipate() === true;
    $needsDocs = $user?->needsIdentityDocuments() === true;
    $activeWhen = in_array($when, ['today', 'tomorrow', 'weekend'], true) ? $when : 'today';
@endphp

<section class="mb-4 space-y-3">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0 space-y-1">
            <div class="mb-1 flex flex-wrap items-center gap-2">
                @if ($isAuth)
                    <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">
                        {{ __('home.welcome_back', ['name' => $user->name]) }}
                    </h1>
                    @if ($isActive)
                        <span class="inline-flex items-center gap-1 rounded-full border border-border bg-surface px-2.5 py-1 text-xs font-medium text-text shadow-sm">
                            <svg class="h-3.5 w-3.5 text-success" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1.06 14.54l-3.48-3.48 1.41-1.41 2.07 2.07 4.24-4.24 1.41 1.41-5.65 5.65z"/>
                            </svg>
                            @if ($cityName)
                                {{ __('home.active_in_city', ['city' => $cityName]) }}
                            @else
                                {{ __('home.active_account') }}
                            @endif
                        </span>
                    @elseif ($needsDocs)
                        <span class="inline-flex items-center gap-1 rounded-full border border-warning/30 bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">
                            {{ __('home.pending_documents') }}
                        </span>
                    @endif
                @else
                    <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">
                        {{ __('home.headline') }}
                    </h1>
                @endif
            </div>

            <p class="max-w-2xl text-sm text-muted sm:text-base">
                {{ $isAuth ? __('home.welcome_sub') : __('home.subheadline') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1 self-start rounded-xl bg-slate-100 p-1 md:self-auto" role="group" aria-label="{{ __('home.date_filter') }}">
            @foreach (['today' => __('home.today'), 'tomorrow' => __('home.tomorrow'), 'weekend' => __('home.weekend')] as $key => $label)
                <a href="{{ route('home', ['when' => $key]) }}"
                   @class([
                       'rounded-lg px-3 py-1.5 text-xs font-medium sm:text-sm',
                       'bg-surface font-semibold text-primary shadow-sm' => $activeWhen === $key,
                       'text-muted hover:text-text' => $activeWhen !== $key,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('events.index') }}"
               class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-muted hover:text-text sm:text-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                    <path d="M3 10h18M8 3v4M16 3v4"/>
                </svg>
                {{ __('home.pick_date') }}
            </a>
        </div>
    </div>
</section>
