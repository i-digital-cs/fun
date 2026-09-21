@extends('layouts.app')

@section('title', __('safety.report_title'))

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-semibold">{{ __('safety.report_title') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('safety.report') }}</p>

        <form method="POST" action="{{ route('reports.store') }}" class="mt-6 space-y-4 rounded-2xl border border-border bg-surface p-6">
            @csrf
            <div>
                <label for="reportable_type" class="mb-1 block text-sm font-medium">{{ __('safety.type') }}</label>
                <select id="reportable_type" name="reportable_type" required class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('reportable_type', $prefill['reportable_type']) === $type->value)>
                            {{ __('safety.types.'.$type->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reportable_id" class="mb-1 block text-sm font-medium">ID</label>
                <input id="reportable_id" name="reportable_id" type="number" min="1" required value="{{ old('reportable_id', $prefill['reportable_id']) }}" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
            </div>
            <div>
                <label for="reason" class="mb-1 block text-sm font-medium">{{ __('safety.reason') }}</label>
                <select id="reason" name="reason" required class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">
                    @foreach ($reasons as $reason)
                        <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>
                            {{ __('safety.reasons.'.$reason->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="description" class="mb-1 block text-sm font-medium">{{ __('safety.description') }}</label>
                <textarea id="description" name="description" rows="4" maxlength="1000" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">{{ __('safety.submit_report') }}</button>
        </form>
    </div>
@endsection
