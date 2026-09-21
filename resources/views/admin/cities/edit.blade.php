@extends('layouts.app')

@section('title', __('location.edit_city'))

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-border bg-surface p-6">
        <h1 class="text-2xl font-semibold">{{ __('location.edit_city') }}</h1>
        <form method="POST" action="{{ route('admin.cities.update', $city) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            @include('admin.cities._form', ['city' => $city])
            <button class="rounded-lg bg-primary px-4 py-2.5 font-semibold text-white">{{ __('location.save') }}</button>
        </form>
    </div>
@endsection
