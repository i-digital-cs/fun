@extends('layouts.dashboard')

@section('title', __('events.members'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.members') }}</h1>
            <a href="{{ event_show_url($event) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ $event->title }}</a>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/80 text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5 text-start font-semibold">{{ __('ui.name') }}</th>
                            <th class="px-5 py-3.5 text-start font-semibold">{{ __('events.role') }}</th>
                            <th class="px-5 py-3.5 text-start font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr class="border-t border-slate-200/70">
                                <td class="px-5 py-3.5 font-medium text-slate-900">{{ $member->user?->name }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $member->role->value }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap gap-3">
                                        @can('manageMembers', $event)
                                            @if ($member->role->value === 'member')
                                                <form method="POST" action="{{ event_route('events.members.promote', $event, ['user' => $member->user_id]) }}">
                                                    @csrf
                                                    <button class="rounded-lg px-2 py-1 font-medium text-indigo-600 transition hover:bg-indigo-50">{{ __('events.promote') }}</button>
                                                </form>
                                            @endif
                                            @if ($member->role->value === 'admin')
                                                <form method="POST" action="{{ event_route('events.members.demote', $event, ['user' => $member->user_id]) }}">
                                                    @csrf
                                                    <button class="rounded-lg px-2 py-1 font-medium text-slate-500 transition hover:bg-slate-50">{{ __('events.demote') }}</button>
                                                </form>
                                            @endif
                                            @if ($member->role->value !== 'owner')
                                                <form method="POST" action="{{ event_route('events.transfer', $event) }}">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $member->user_id }}">
                                                    <button class="rounded-lg px-2 py-1 font-medium text-orange-600 transition hover:bg-orange-50">{{ __('events.transfer_ownership') }}</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @if ($member->user_id !== auth()->id() && auth()->user()->canParticipate())
                                            <a href="{{ route('reports.create', ['reportable_type' => 'user', 'reportable_id' => $member->user_id]) }}" class="rounded-lg px-2 py-1 font-medium text-red-600 transition hover:bg-red-50">{{ __('safety.report') }}</a>
                                            <form method="POST" action="{{ route('users.block', $member->user_id) }}">
                                                @csrf
                                                <button class="rounded-lg px-2 py-1 font-medium text-slate-500 transition hover:bg-slate-50">{{ __('safety.block') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $members->links() }}
        </div>
    </div>
@endsection
