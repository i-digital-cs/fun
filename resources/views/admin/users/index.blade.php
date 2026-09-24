@extends('layouts.admin')

@section('title', __('admin.users'))
@section('topbar_title', __('admin.users'))

@section('content')
@php
    $tabs = [
        'active' => __('admin.status_active'),
        'pending_documents' => __('admin.status_pending'),
        'deactivated' => __('admin.status_deactivated'),
        'all' => __('admin.status_all'),
    ];
@endphp

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.users') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('admin.users_sub') }}</p>
    </div>
    <form method="GET" action="{{ route('admin.users.index') }}" class="live-search relative flex flex-wrap items-center gap-2"
          data-live-search
          data-live-search-type="users"
          data-live-search-url="{{ route('search.suggest') }}"
          data-live-search-empty="{{ __('ui.search_empty') }}"
          data-live-search-see-all="{{ __('ui.search_see_all') }}"
          data-live-search-error="{{ __('ui.search_failed') }}">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="search"
               name="q"
               value="{{ $q }}"
               placeholder="{{ __('admin.search_users') }}"
               autocomplete="off"
               data-live-search-input
               class="h-10 w-56 rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        <button type="submit" class="h-10 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white">{{ __('admin.filter') }}</button>
        <div class="live-search__panel absolute end-0 top-full z-20 mt-2 w-full min-w-[18rem]" data-live-search-panel hidden>
            <div class="live-search__searching" data-live-search-searching hidden>
                <x-ui.searching size="sm" />
            </div>
            <div class="live-search__results" data-live-search-results></div>
        </div>
    </form>
</div>

<div class="mb-5 flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.users.index', array_filter(['status' => $key, 'q' => $q])) }}"
           @class([
               'rounded-full px-4 py-2 text-sm font-semibold no-underline transition',
               'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-600/25' => $status === $key,
               'border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-700' => $status !== $key,
           ])>
            {{ $label }}
            <span @class([
                'ms-1 rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                'bg-white/20 text-white' => $status === $key,
                'bg-slate-100 text-slate-500' => $status !== $key,
            ])>{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($users as $user)
        <details class="group overflow-hidden rounded-[18px] border border-[#e7ebf3] bg-white shadow-[0_12px_35px_rgba(15,23,42,0.04)] open:shadow-md">
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 px-4 py-4 marker:content-none [&::-webkit-details-marker]:hidden sm:px-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                    {{ user_initials($user->name) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-extrabold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    @php($authMethod = $user->authMethod())
                    <span @class([
                        'rounded-full px-2.5 py-1',
                        'bg-sky-50 text-sky-700' => $authMethod === 'google',
                        'bg-slate-100 text-slate-600' => $authMethod === 'password',
                        'bg-violet-50 text-violet-700' => $authMethod === 'both',
                    ])>{{ $user->authMethodLabel() }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $user->status->value }}</span>
                    @if ($user->city)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">{{ $user->city->localizedName() }}</span>
                    @endif
                    <span class="rounded-full bg-orange-50 px-2.5 py-1 text-orange-700">
                        {{ __('admin.created_events_count', ['count' => $user->created_events_count]) }}
                    </span>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">
                        {{ __('admin.memberships_count', ['count' => $user->memberships_count]) }}
                    </span>
                </div>
                <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 9l6 6 6-6"/>
                </svg>
            </summary>

            <div class="border-t border-slate-100 px-4 py-4 sm:px-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-extrabold text-slate-900">{{ __('admin.user_events') }}</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        @can('update', $user)
                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 no-underline hover:bg-indigo-100">
                                {{ __('admin.edit_user') }}
                            </a>
                        @endcan
                        @if ($user->isDeactivated())
                            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                                @csrf
                                <button class="rounded-xl bg-emerald-500 px-3 py-2 text-xs font-bold text-white">{{ __('admin.reactivate') }}</button>
                            </form>
                        @elseif (! $user->isPlatformAdmin() || auth()->user()->isSuperAdmin())
                            @can('deactivate', $user)
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="flex flex-wrap items-center gap-2" data-confirm="{{ __('ui.confirm_deactivate_user') }}">
                                    @csrf
                                    <input type="text" name="reason" placeholder="{{ __('admin.reason') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs">
                                    <button class="rounded-xl bg-red-500 px-3 py-2 text-xs font-bold text-white">{{ __('admin.deactivate') }}</button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </div>

                @if ($user->createdEvents->isEmpty())
                    <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        {{ __('admin.no_user_events') }}
                    </p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-100">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-2.5 text-start">{{ __('events.title') }}</th>
                                    <th class="px-3 py-2.5 text-start">{{ __('events.category') }}</th>
                                    <th class="px-3 py-2.5 text-start">{{ __('events.city') }}</th>
                                    <th class="px-3 py-2.5 text-start">{{ __('events.starts_at') }}</th>
                                    <th class="px-3 py-2.5 text-start">{{ __('admin.status') }}</th>
                                    <th class="px-3 py-2.5 text-start"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user->createdEvents as $event)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-3 py-2.5 font-semibold text-slate-800">{{ $event->title ?: '—' }}</td>
                                        <td class="px-3 py-2.5 text-slate-600">{{ $event->category?->localizedName() ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-slate-600">{{ $event->city?->localizedName() ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-slate-600">{{ $event->starts_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                        <td class="px-3 py-2.5">
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $event->status->value }}</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-end">
                                            <a href="{{ event_show_url($event) }}" class="text-xs font-bold text-indigo-600 no-underline hover:text-indigo-800">{{ __('admin.view') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($user->created_events_count > $user->createdEvents->count())
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('admin.showing_events_of', ['shown' => $user->createdEvents->count(), 'total' => $user->created_events_count]) }}
                        </p>
                    @endif
                @endif
            </div>
        </details>
    @empty
        <div class="rounded-[18px] border border-dashed border-slate-200 bg-white px-4 py-12 text-center text-sm text-slate-500">
            {{ __('admin.no_users') }}
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $users->links() }}</div>
@endsection
