@extends('layouts.app')

@section('title', __('identity.title'))

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-text">{{ __('identity.title') }}</h1>
        <p class="mt-2 text-sm text-muted">{{ __('identity.help') }}</p>

        <form method="POST" action="{{ route('identity.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @foreach ([
                'id_front' => __('identity.id_front'),
                'id_back' => __('identity.id_back'),
                'selfie' => __('identity.selfie'),
            ] as $name => $label)
                <div>
                    <label class="mb-2 block text-sm font-medium" for="{{ $name }}">{{ $label }}</label>
                    <input id="{{ $name }}" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" required
                           class="w-full rounded-xl border border-dashed border-border bg-background px-4 py-8 text-sm">
                </div>
            @endforeach

            <button type="submit" class="w-full rounded-lg bg-accent px-4 py-2.5 font-semibold text-white">
                {{ __('identity.submit') }}
            </button>
        </form>
    </div>
@endsection
