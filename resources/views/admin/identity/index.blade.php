@extends('layouts.app')

@section('title', __('admin.identity_documents'))

@section('content')
    @include('admin._nav')

    <h1 class="text-2xl font-semibold">{{ __('admin.identity_documents') }}</h1>

    <div class="mt-4 overflow-x-auto rounded-xl border border-border bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-background text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('ui.name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('ui.email') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.documents') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.status') }}</th>
                    <th class="px-4 py-3 text-start"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr class="border-t border-border">
                        <td class="px-4 py-3">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->identity_documents_count }}</td>
                        <td class="px-4 py-3">{{ $user->status->value }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.identity.show', $user) }}" class="font-medium text-primary">{{ __('admin.view') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
