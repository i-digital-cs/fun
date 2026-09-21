@extends('layouts.app')

@section('title', __('events.members'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold">{{ __('events.members') }}</h1>
            <a href="{{ route('events.show', $event) }}" class="text-sm text-primary">{{ $event->title }}</a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border bg-surface">
            <table class="min-w-full text-sm">
                <thead class="bg-background text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('ui.name') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('events.role') }}</th>
                        <th class="px-4 py-3 text-start">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($event->members as $member)
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">{{ $member->user?->name }}</td>
                            <td class="px-4 py-3">{{ $member->role->value }}</td>
                            <td class="px-4 py-3">
                                @can('manageMembers', $event)
                                    <div class="flex flex-wrap gap-3">
                                        @if ($member->role->value === 'member')
                                            <form method="POST" action="{{ route('events.members.promote', [$event, $member->user_id]) }}">
                                                @csrf
                                                <button class="font-medium text-primary">{{ __('events.promote') }}</button>
                                            </form>
                                        @endif
                                        @if ($member->role->value === 'admin')
                                            <form method="POST" action="{{ route('events.members.demote', [$event, $member->user_id]) }}">
                                                @csrf
                                                <button class="font-medium text-muted">{{ __('events.demote') }}</button>
                                            </form>
                                        @endif
                                        @if ($member->role->value !== 'owner')
                                            <form method="POST" action="{{ route('events.transfer', $event) }}">
                                                @csrf
                                                <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                                <button class="font-medium text-accent">{{ __('events.transfer_ownership') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
