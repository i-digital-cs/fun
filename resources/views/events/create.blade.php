@extends('layouts.app')

@section('title', __('events.create_event'))

@section('content')
    <div class="mx-auto max-w-2xl rounded-2xl border border-border bg-surface p-6">
        <h1 class="text-2xl font-semibold">{{ __('events.create_event') }}</h1>
        <form method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @include('events._form')
            <button class="rounded-lg bg-accent px-4 py-2.5 font-semibold text-white">{{ __('events.create_event') }}</button>
        </form>
    </div>
@endsection
