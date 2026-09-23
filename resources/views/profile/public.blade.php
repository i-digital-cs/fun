@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', $profile['name'].' · '.$profile['public_id'])

@section('content')
    <div class="mx-auto max-w-2xl">
        <article class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <header class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 bg-gradient-to-br from-indigo-50/90 via-white to-orange-50/50 px-5 py-5 sm:px-7">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-indigo-600">{{ __('profile.public_profile') }}</p>
                    <h1 class="mt-0.5 truncate text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                        {{ $profile['name'] }}
                    </h1>
                    @if (! empty($profile['public_id']))
                        <p class="mt-1 font-mono text-xs font-semibold text-slate-400">{{ $profile['public_id'] }}</p>
                    @endif
                </div>
                @auth
                    @if ((int) auth()->id() === (int) $profileUser->id)
                        <a href="{{ route('settings.profile.edit') }}"
                           class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-indigo-300 hover:text-indigo-700">
                            {{ __('profile.edit_settings') }}
                        </a>
                    @endif
                @endauth
            </header>

            <div class="space-y-8 px-5 py-6 sm:px-7 sm:py-8">
                <x-profile.public-card :profile="$profile" class="w-full">
                    @auth
                        @if ((int) auth()->id() !== (int) $profileUser->id && auth()->user()->canParticipate())
                            <a href="{{ route('reports.create', ['reportable_type' => 'user', 'reportable_id' => $profileUser->id]) }}"
                               class="profile-card__request">
                                {{ __('safety.report') }}
                            </a>
                            <form method="POST" action="{{ route('users.block', $profileUser) }}" class="w-full sm:w-auto sm:flex-1">
                                @csrf
                                <button type="submit" class="profile-card__request profile-card__request--muted">
                                    {{ __('safety.block') }}
                                </button>
                            </form>
                        @elseif ((int) auth()->id() === (int) $profileUser->id)
                            <a href="{{ route('settings.profile.edit') }}" class="profile-card__request profile-card__request--primary">
                                {{ __('profile.edit_settings') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="profile-card__request profile-card__request--primary">
                            {{ __('ui.login') }}
                        </a>
                    @endauth
                </x-profile.public-card>

                @if ($hostedEvents->isNotEmpty())
                    <section class="space-y-3">
                        <h2 class="text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">
                            {{ __('profile.hosted_events') }}
                        </h2>
                        <div class="grid gap-3">
                            @foreach ($hostedEvents as $event)
                                <a href="{{ route('events.show', $event) }}"
                                   class="flex items-start justify-between gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white hover:shadow-md">
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-900">{{ $event->title }}</p>
                                        <p class="mt-1 text-xs font-medium text-indigo-600">
                                            {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $event->city?->localizedName() }}
                                            · {{ $event->members_count }} / {{ $event->max_participants }}
                                        </p>
                                    </div>
                                    <span class="mt-0.5 inline-flex shrink-0 rounded-lg bg-indigo-50 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-indigo-600">
                                        {{ __('events.view_event') }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </article>
    </div>
@endsection
