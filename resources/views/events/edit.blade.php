@extends('layouts.app')

@section('title', __('events.edit_event'))

@section('content')
    <div class="mx-auto max-w-2xl rounded-2xl border border-border bg-surface p-6">
        <h1 class="text-2xl font-semibold">{{ __('events.edit_event') }}</h1>
        <form method="POST" action="{{ route('events.update', $event) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            @include('events._form', ['event' => $event])
            <button class="rounded-lg bg-primary px-4 py-2.5 font-semibold text-white">{{ __('events.save') }}</button>
        </form>
    </div>
@endsection
