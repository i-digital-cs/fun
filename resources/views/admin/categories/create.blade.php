@extends('layouts.admin')

@section('title', __('catalog.add_category'))

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-border bg-surface p-6">
        <h1 class="text-2xl font-semibold">{{ __('catalog.add_category') }}</h1>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-6 space-y-4">
            @csrf
            @include('admin.categories._form')
            <button class="rounded-lg bg-accent px-4 py-2.5 font-semibold text-white">{{ __('catalog.add_category') }}</button>
        </form>
    </div>
@endsection
