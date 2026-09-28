@extends('layouts.dashboard')

@section('title', __('friends.notify_friend_events'))

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('friends.notify_friend_events') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('friends.notify_friend_events_help') }}</p>

        @if (session('status'))
            <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('settings.notifications.update') }}" class="mt-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            <label class="flex cursor-pointer items-start gap-3">
                <input
                    type="checkbox"
                    name="notify_friend_events"
                    value="1"
                    @checked(old('notify_friend_events', $user->notify_friend_events))
                    class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                >
                <span>
                    <span class="block text-sm font-bold text-slate-900">{{ __('friends.notify_friend_events') }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500">{{ __('friends.notify_friend_events_help') }}</span>
                </span>
            </label>
            <button type="submit" class="mt-6 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                {{ __('friends.save') }}
            </button>
        </form>
    </div>
@endsection
