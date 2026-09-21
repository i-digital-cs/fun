@extends('layouts.app')

@section('title', __('admin.dashboard'))

@section('content')
    @include('admin._nav')

    <h1 class="text-2xl font-semibold">{{ __('admin.dashboard') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ $siteName }}</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($stats as $key => $value)
            <div class="rounded-2xl border border-border bg-surface p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ __('admin.stat_'.$key) }}</p>
                <p class="mt-2 text-3xl font-semibold">{{ $value }}</p>
            </div>
        @endforeach
    </div>
@endsection
