@extends('layouts.app')

@section('title', __('catalog.categories'))

@section('content')
    @include('admin._nav')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('catalog.categories') }}</h1>
        <a href="{{ route('admin.categories.create') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">
            {{ __('catalog.add_category') }}
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-border bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-background text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('catalog.sort_order') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('catalog.name_en') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('catalog.name_ar') }}</th>
                    <th class="px-4 py-3 text-start">Status</th>
                    <th class="px-4 py-3 text-start">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr class="border-t border-border">
                        <td class="px-4 py-3">{{ $category->sort_order }}</td>
                        <td class="px-4 py-3">{{ $category->name_en }}</td>
                        <td class="px-4 py-3">{{ $category->name_ar }}</td>
                        <td class="px-4 py-3">
                            {{ $category->is_active ? __('catalog.active') : __('catalog.inactive') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="font-medium text-primary">{{ __('catalog.edit_category') }}</a>
                                @if ($category->is_active)
                                    <form method="POST" action="{{ route('admin.categories.deactivate', $category) }}">
                                        @csrf
                                        <button class="font-medium text-danger">{{ __('admin.deactivate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.categories.activate', $category) }}">
                                        @csrf
                                        <button class="font-medium text-success">{{ __('admin.reactivate') }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $categories->links() }}</div>
@endsection
