@extends('layouts.app')

@section('title', __('safety.reports'))

@section('content')
    @include('admin._nav')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('safety.reports') }}</h1>
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex gap-2">
            <select name="status" class="rounded-lg border border-border bg-surface px-3 py-2 text-sm">
                <option value="">{{ __('safety.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($currentStatus === $status->value)>
                        {{ __('safety.statuses.'.$status->value) }}
                    </option>
                @endforeach
            </select>
            <button class="rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white">{{ __('safety.filter') }}</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($reports as $report)
            <div class="rounded-xl border border-border bg-surface p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium">
                            {{ __('safety.types.'.$report->reportable_type->value) }} #{{ $report->reportable_id }}
                            · {{ __('safety.reasons.'.$report->reason->value) }}
                        </p>
                        <p class="mt-1 text-sm text-muted">
                            {{ $report->reporter?->name }} · {{ __('safety.statuses.'.$report->status->value) }}
                            · {{ $report->created_at?->format('Y-m-d H:i') }}
                        </p>
                        @if ($report->description)
                            <p class="mt-2 whitespace-pre-wrap text-sm">{{ $report->description }}</p>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="mt-4 grid gap-2 sm:grid-cols-3">
                    @csrf
                    @method('PUT')
                    <select name="status" class="rounded-lg border border-border bg-background px-3 py-2 text-sm">
                        @foreach ($statuses as $status)
                            @continue($status->value === 'pending')
                            <option value="{{ $status->value }}" @selected($report->status->value === $status->value)>
                                {{ __('safety.statuses.'.$status->value) }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="moderation_action" value="{{ old('moderation_action', $report->moderation_action) }}" placeholder="{{ __('safety.moderation_action') }}" class="rounded-lg border border-border bg-background px-3 py-2 text-sm sm:col-span-1">
                    <button class="rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white">{{ __('safety.update_report') }}</button>
                </form>
            </div>
        @empty
            <p class="text-muted">{{ __('events.empty') }}</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $reports->links() }}</div>
@endsection
