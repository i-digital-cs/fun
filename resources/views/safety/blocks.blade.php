@extends('layouts.app')

@section('title', __('safety.blocked_users'))

@section('content')
    <div class="mb-4">
        <h1 class="text-2xl font-semibold">{{ __('safety.blocked_users') }}</h1>
    </div>

    @if ($blocks->isEmpty())
        <p class="text-muted">{{ __('safety.no_blocks') }}</p>
    @else
        <div class="space-y-3">
            @foreach ($blocks as $block)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-border bg-surface p-4">
                    <div>
                        <p class="font-medium">{{ $block->blocked?->name }}</p>
                        <p class="text-sm text-muted">{{ $block->blocked?->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('users.unblock', $block->blocked_id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg border border-border px-3 py-2 text-sm font-medium">{{ __('safety.unblock') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $blocks->links() }}</div>
    @endif
@endsection
