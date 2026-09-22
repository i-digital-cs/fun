@extends('layouts.admin')

@section('title', __('admin.moderate_chat').' — '.$event->title)

@section('content')

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">{{ __('admin.moderate_chat') }}</h1>
            <p class="text-sm text-muted">{{ $event->title }}</p>
        </div>
        <a href="{{ route('admin.events.index') }}" class="rounded-lg border border-border px-3 py-2 text-sm">{{ __('admin.events') }}</a>
    </div>

    <div class="space-y-3">
        @forelse ($messages as $message)
            <div class="flex items-start justify-between gap-3 rounded-xl border border-border bg-surface p-4">
                <div>
                    <p class="text-sm font-semibold">{{ $message->user?->name }}</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                    <p class="mt-1 text-xs text-muted">{{ $message->created_at?->format('Y-m-d H:i') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.chat.destroy', [$event, $message]) }}">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg bg-danger px-3 py-1.5 text-sm font-medium text-white">{{ __('admin.delete') }}</button>
                </form>
            </div>
        @empty
            <p class="text-muted">{{ __('chat.empty_thread') }}</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
