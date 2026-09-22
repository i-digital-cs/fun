@extends('layouts.admin')

@section('title', __('admin.audit'))

@section('content')

    <h1 class="text-2xl font-semibold">{{ __('admin.audit') }}</h1>

    <div class="mt-4 space-y-3">
        @forelse ($logs as $log)
            <div class="rounded-xl border border-border bg-surface p-4 text-sm">
                <p class="font-medium">{{ $log->action }}</p>
                <p class="mt-1 text-muted">
                    {{ $log->actor?->name }} · {{ $log->created_at?->format('Y-m-d H:i') }}
                    @if ($log->subject_type)
                        · {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                    @endif
                </p>
                @if ($log->meta)
                    <pre class="mt-2 overflow-x-auto rounded-lg bg-background p-2 text-xs text-muted">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
        @empty
            <p class="text-muted">{{ __('admin.no_audit') }}</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
