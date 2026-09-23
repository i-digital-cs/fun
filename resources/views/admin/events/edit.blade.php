@extends('layouts.admin')

@section('title', __('admin.edit_event'))
@section('topbar_title', __('admin.edit_event'))

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.edit_event') }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ $event->creator?->name }} · {{ $event->status->value }}
        </p>
    </div>
    <a href="{{ route('admin.events.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 no-underline hover:border-indigo-300 hover:text-indigo-700">
        {{ __('admin.events_list') }}
    </a>
</div>

<div class="mx-auto max-w-2xl rounded-[18px] border border-[#e7ebf3] bg-white p-6 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        @include('events._form', ['event' => $event, 'categories' => $categories, 'cities' => $cities])

        <div>
            <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('admin.status') }}</label>
            <select id="status" name="status" required
                    class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $event->status->value) === $status->value)>
                        {{ $status->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                {{ __('admin.save_event') }}
            </button>
            <a href="{{ route('events.show', $event) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 no-underline hover:border-indigo-300">
                {{ __('admin.view') }}
            </a>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-6 border-t border-slate-100 pt-6"
          data-confirm="{{ __('admin.confirm_delete_event') }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="rounded-xl bg-red-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-600">
            {{ __('admin.delete_event') }}
        </button>
        <p class="mt-2 text-xs text-slate-500">{{ __('admin.delete_event_help') }}</p>
    </form>
</div>
@endsection
