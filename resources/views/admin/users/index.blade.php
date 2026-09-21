@extends('layouts.app')

@section('title', __('admin.users'))

@section('content')
    <div class="space-y-4">
        <h1 class="text-2xl font-semibold">{{ __('admin.users') }}</h1>
        <div class="overflow-x-auto rounded-xl border border-border bg-surface">
            <table class="min-w-full text-sm">
                <thead class="bg-background text-muted">
                    <tr>
                        <th class="px-4 py-3 text-start">ID</th>
                        <th class="px-4 py-3 text-start">{{ __('ui.name') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('ui.email') }}</th>
                        <th class="px-4 py-3 text-start">Status</th>
                        <th class="px-4 py-3 text-start">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">{{ $user->id }}</td>
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->status->value }}</td>
                            <td class="px-4 py-3">
                                @if ($user->isDeactivated())
                                    <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                                        @csrf
                                        <button class="rounded-lg bg-success px-3 py-1.5 font-medium text-white">{{ __('admin.reactivate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="text" name="reason" placeholder="{{ __('admin.reason') }}" class="rounded-lg border border-border px-2 py-1">
                                        <button class="rounded-lg bg-danger px-3 py-1.5 font-medium text-white">{{ __('admin.deactivate') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
@endsection
