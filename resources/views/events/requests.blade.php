@extends('layouts.app')

@section('title', __('events.join_requests'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">{{ __('events.join_requests') }}</h1>
            <a href="{{ route('events.show', $event) }}" class="text-sm text-primary">{{ $event->title }}</a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border bg-surface">
            <table class="min-w-full text-sm">
                <thead class="bg-background text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('ui.name') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('events.status') }}</th>
                        <th class="px-4 py-3 text-start">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $joinRequest)
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">{{ $joinRequest->user?->name }}</td>
                            <td class="px-4 py-3">{{ $joinRequest->status->value }}</td>
                            <td class="px-4 py-3">
                                @if ($joinRequest->status->value === 'pending')
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('events.requests.accept', [$event, $joinRequest]) }}">
                                            @csrf
                                            <button class="rounded-lg bg-success px-3 py-1.5 font-medium text-white">{{ __('events.accept') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('events.requests.reject', [$event, $joinRequest]) }}">
                                            @csrf
                                            <button class="rounded-lg bg-danger px-3 py-1.5 font-medium text-white">{{ __('events.reject') }}</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-muted">{{ __('events.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
