@extends('layouts.admin')

@section('title', __('catalog.edit_category'))

@section('content')
    <div class="mx-auto max-w-xl rounded-2xl border border-border bg-surface p-6">
        <h1 class="text-2xl font-semibold">{{ __('catalog.edit_category') }}</h1>
        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            @include('admin.categories._form', ['category' => $category])
            <button class="rounded-lg bg-primary px-4 py-2.5 font-semibold text-white">{{ __('catalog.save') }}</button>
        </form>
    </div>
@endsection
