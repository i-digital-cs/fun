@extends('layouts.admin')

@section('title', __('location.cities'))

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('location.cities') }}</h1>
        <a href="{{ route('admin.cities.create') }}" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white">
            {{ __('location.add_city') }}
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-border bg-surface">
        <table class="min-w-full text-sm">
            <thead class="bg-background text-muted">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('location.name_en') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('location.name_ar') }}</th>
                    <th class="px-4 py-3 text-start">Lat/Lng</th>
                    <th class="px-4 py-3 text-start">Status</th>
                    <th class="px-4 py-3 text-start">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cities as $city)
                    <tr class="border-t border-border">
                        <td class="px-4 py-3">{{ $city->name_en }}</td>
                        <td class="px-4 py-3">{{ $city->name_ar }}</td>
                        <td class="px-4 py-3">{{ $city->latitude }}, {{ $city->longitude }}</td>
                        <td class="px-4 py-3">
                            {{ $city->is_active ? __('location.active') : __('location.inactive') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.cities.edit', $city) }}" class="text-primary font-medium">{{ __('location.edit_city') }}</a>
                                @if ($city->is_active)
                                    <form method="POST" action="{{ route('admin.cities.deactivate', $city) }}" data-confirm="{{ __('ui.confirm_deactivate') }}">
                                        @csrf
                                        <button class="text-danger font-medium">{{ __('admin.deactivate') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.cities.activate', $city) }}">
                                        @csrf
                                        <button class="text-success font-medium">{{ __('admin.reactivate') }}</button>
                                    </form>
                                @endif
                                @can('delete', $city)
                                    @if ($city->events_count === 0)
                                        <form method="POST" action="{{ route('admin.cities.destroy', $city) }}"
                                              data-confirm="{{ __('location.confirm_delete_city') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-danger font-medium">{{ __('location.remove_city') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-muted" title="{{ __('location.city_has_events') }}">
                                            {{ __('location.events_count', ['count' => $city->events_count]) }}
                                        </span>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $cities->links() }}</div>
@endsection
