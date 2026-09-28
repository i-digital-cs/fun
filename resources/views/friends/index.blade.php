@extends('layouts.dashboard')

@section('title', __('friends.title'))

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('friends.title') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('friends.search_hint') }}</p>
            </div>
        </div>

        @if (session('status'))
            <p class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('friends.index') }}" class="mb-5">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="sr-only" for="friend-search-q">{{ __('friends.search_placeholder') }}</label>
            <div class="flex gap-2">
                <input
                    id="friend-search-q"
                    type="search"
                    name="q"
                    value="{{ $q }}"
                    placeholder="{{ __('friends.search_placeholder') }}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm shadow-sm outline-none ring-indigo-500/30 placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4"
                >
                <button type="submit" class="shrink-0 rounded-2xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    {{ __('friends.search_title') }}
                </button>
            </div>
        </form>

        @if ($results->isNotEmpty())
            <section class="mb-6 space-y-2">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('friends.search_title') }}</h2>
                @foreach ($results as $person)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                        <a href="{{ $person['profile_url'] }}" class="flex min-w-0 items-center gap-3 no-underline">
                            @if (! empty($person['profile_image_path']))
                                <img src="{{ $person['profile_image_path'] }}" alt="" class="h-11 w-11 rounded-full object-cover">
                            @else
                                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black text-white">{{ $person['initials'] }}</span>
                            @endif
                            <span class="min-w-0">
                                <span class="block truncate font-bold text-slate-900">{{ $person['name'] }}</span>
                                <span class="block font-mono text-xs text-slate-400">{{ $person['public_id'] }}</span>
                            </span>
                        </a>
                        <div class="flex flex-wrap gap-2">
                            @php $fs = $person['friendship'] ?? []; @endphp
                            @if (($fs['status'] ?? null) === 'accepted')
                                <a href="{{ route('dms.open', $person['id']) }}" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-bold text-white">{{ __('friends.message') }}</a>
                                <form method="POST" action="{{ route('friends.destroy', $person['id']) }}">@csrf @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ __('friends.unfriend') }}</button>
                                </form>
                            @elseif (($fs['status'] ?? null) === 'pending' && ($fs['direction'] ?? null) === 'outgoing')
                                <form method="POST" action="{{ route('friends.cancel', $person['id']) }}">@csrf
                                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ __('friends.cancel_request') }}</button>
                                </form>
                            @elseif (($fs['status'] ?? null) === 'pending' && ($fs['direction'] ?? null) === 'incoming')
                                <form method="POST" action="{{ route('friends.accept', $person['id']) }}">@csrf
                                    <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white">{{ __('friends.accept') }}</button>
                                </form>
                                <form method="POST" action="{{ route('friends.decline', $person['id']) }}">@csrf
                                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ __('friends.decline') }}</button>
                                </form>
                            @elseif ($fs['can_request'] ?? false)
                                <form method="POST" action="{{ route('friends.store') }}">@csrf
                                    <input type="hidden" name="public_id" value="{{ $person['public_id'] }}">
                                    <button type="submit" class="rounded-xl bg-orange-500 px-3 py-2 text-xs font-bold text-white">{{ __('friends.add_friend') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        <div class="mb-4 flex rounded-2xl bg-indigo-50/80 p-1 ring-1 ring-indigo-100" role="tablist">
            <a href="{{ route('friends.index', ['tab' => 'friends', 'q' => $q ?: null]) }}"
               @class([
                   'flex-1 rounded-xl px-3 py-2 text-center text-sm font-bold transition',
                   'bg-white text-indigo-700 shadow-sm' => $tab === 'friends',
                   'text-slate-600 hover:text-slate-900' => $tab !== 'friends',
               ])>{{ __('friends.tab_friends') }}</a>
            <a href="{{ route('friends.index', ['tab' => 'requests', 'q' => $q ?: null]) }}"
               @class([
                   'flex flex-1 items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-bold transition',
                   'bg-white text-indigo-700 shadow-sm' => $tab === 'requests',
                   'text-slate-600 hover:text-slate-900' => $tab !== 'requests',
               ])>
                {{ __('friends.tab_requests') }}
                @if ($incoming->isNotEmpty())
                    <span class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $incoming->count() }}</span>
                @endif
            </a>
        </div>

        @if ($tab === 'friends')
            @if ($friends->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                    <p class="text-sm text-slate-500">{{ __('friends.friends_empty') }}</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($friends as $person)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                            <a href="{{ $person['profile_url'] }}" class="flex min-w-0 items-center gap-3 no-underline">
                                @if (! empty($person['profile_image_path']))
                                    <img src="{{ $person['profile_image_path'] }}" alt="" class="h-11 w-11 rounded-full object-cover">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black text-white">{{ $person['initials'] }}</span>
                                @endif
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-slate-900">{{ $person['name'] }}</span>
                                    <span class="block font-mono text-xs text-slate-400">{{ $person['public_id'] }}</span>
                                </span>
                            </a>
                            <div class="flex gap-2">
                                <a href="{{ route('dms.open', $person['id']) }}" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-bold text-white">{{ __('friends.message') }}</a>
                                <form method="POST" action="{{ route('friends.destroy', $person['id']) }}">@csrf @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ __('friends.unfriend') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="space-y-6">
                <section>
                    <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('friends.requests_title') }}</h2>
                    @forelse ($incoming as $row)
                        @php $person = $row->requester; @endphp
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                            <a href="{{ $person->publicProfileUrl() }}" class="flex min-w-0 items-center gap-3 no-underline">
                                @if ($person->profile_image_path)
                                    <img src="{{ $person->profile_image_path }}" alt="" class="h-11 w-11 rounded-full object-cover">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black text-white">{{ user_initials($person->name) }}</span>
                                @endif
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-slate-900">{{ $person->name }}</span>
                                    <span class="block font-mono text-xs text-slate-400">{{ $person->public_id }}</span>
                                </span>
                            </a>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('friends.accept', $person) }}">@csrf
                                    <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white">{{ __('friends.accept') }}</button>
                                </form>
                                <form method="POST" action="{{ route('friends.decline', $person) }}">@csrf
                                    <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ __('friends.decline') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">{{ __('friends.requests_empty') }}</p>
                    @endforelse
                </section>

                <section>
                    <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('friends.pending_outgoing') }}</h2>
                    @forelse ($outgoing as $row)
                        @php $person = $row->addressee; @endphp
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                            <a href="{{ $person->publicProfileUrl() }}" class="flex min-w-0 items-center gap-3 no-underline">
                                @if ($person->profile_image_path)
                                    <img src="{{ $person->profile_image_path }}" alt="" class="h-11 w-11 rounded-full object-cover">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-black text-white">{{ user_initials($person->name) }}</span>
                                @endif
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-slate-900">{{ $person->name }}</span>
                                    <span class="block font-mono text-xs text-slate-400">{{ $person->public_id }}</span>
                                </span>
                            </a>
                            <form method="POST" action="{{ route('friends.cancel', $person) }}">@csrf
                                <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">{{ __('friends.cancel_request') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">{{ __('friends.outgoing_empty') }}</p>
                    @endforelse
                </section>
            </div>
        @endif
    </div>
@endsection
