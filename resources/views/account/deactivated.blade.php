@extends('layouts.dashboard')

@section('title', __('ui.account_deactivated_title'))

@section('content')
    <div class="mx-auto max-w-lg rounded-2xl border border-danger/20 bg-surface p-6 text-center shadow-sm">
        <h1 class="text-2xl font-semibold text-danger">{{ __('ui.account_deactivated_title') }}</h1>
        <p class="mt-3 text-muted">{{ __('ui.account_deactivated_body') }}</p>
    </div>
@endsection
