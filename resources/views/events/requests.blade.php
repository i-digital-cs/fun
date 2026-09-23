@extends('layouts.dashboard')

@section('title', __('events.join_requests'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.join_requests') }}</h1>
            <a href="{{ route('events.show', $event) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ $event->title }}</a>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5 text-start font-semibold">{{ __('ui.name') }}</th>
                            <th class="px-5 py-3.5 text-start font-semibold">{{ __('events.status') }}</th>
                            <th class="px-5 py-3.5 text-start font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $joinRequest)
                            <tr class="border-t border-slate-200/70">
                                <td class="px-5 py-3.5 font-medium text-slate-900">{{ $joinRequest->user?->name }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $joinRequest->status->value }}</td>
                                <td class="px-5 py-3.5">
                                    @if ($joinRequest->status->value === 'pending')
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('events.requests.accept', [$event, $joinRequest]) }}">
                                                @csrf
                                                <button class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-3 py-1.5 text-sm font-semibold text-white transition hover:brightness-105">{{ __('events.accept') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('events.requests.reject', [$event, $joinRequest]) }}" class="cancel-btn-slot py-2" data-confirm="{{ __('ui.confirm_reject_request') }}">
                                                @csrf
                                                <x-ui.cancel-btn type="submit" size="sm" :label="__('events.reject')" />
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">{{ __('events.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
