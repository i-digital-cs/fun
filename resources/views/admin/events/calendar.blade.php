@extends('layouts.admin')

@section('title', __('admin.events_calendar'))
@section('topbar_title', __('admin.events_calendar'))

@section('content')
@php
    $filterQuery = array_filter([
        'status' => $status ?: null,
        'category_id' => $categoryId ?: null,
        'city_id' => $cityId ?: null,
        'q' => $q !== '' ? $q : null,
    ], fn ($v) => $v !== null && $v !== '');

    $dayNames = [
        __('admin.cal_sun'),
        __('admin.cal_mon'),
        __('admin.cal_tue'),
        __('admin.cal_wed'),
        __('admin.cal_thu'),
        __('admin.cal_fri'),
        __('admin.cal_sat'),
    ];

    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'published' => 'bg-indigo-100 text-indigo-800',
        'cancelled' => 'bg-red-100 text-red-700',
        'completed' => 'bg-emerald-100 text-emerald-800',
    ];
@endphp

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.events_calendar') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('admin.events_calendar_sub', ['count' => $eventCount]) }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.events.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 no-underline hover:border-indigo-300 hover:text-indigo-700">
            {{ __('admin.events_list') }}
        </a>
    </div>
</div>

<form method="GET" action="{{ route('admin.events.calendar') }}" class="mb-5 grid gap-3 rounded-[18px] border border-[#e7ebf3] bg-white p-4 shadow-[0_12px_35px_rgba(15,23,42,0.04)] sm:grid-cols-2 lg:grid-cols-6">
    <input type="hidden" name="year" value="{{ $cursor->year }}">
    <input type="hidden" name="month" value="{{ $cursor->month }}">

    <div class="lg:col-span-2">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.search') }}</label>
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('admin.search_events') }}"
               class="h-10 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
    </div>

    <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.status') }}</label>
        <select name="status" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            <option value="">{{ __('admin.all_statuses') }}</option>
            @foreach (['draft', 'published', 'cancelled', 'completed'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('events.category') }}</label>
        <select name="category_id" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            <option value="">{{ __('admin.all_categories') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) $categoryId === (int) $category->id)>{{ $category->localizedName() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('events.city') }}</label>
        <select name="city_id" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            <option value="">{{ __('admin.all_cities') }}</option>
            @foreach ($cities as $city)
                <option value="{{ $city->id }}" @selected((int) $cityId === (int) $city->id)>{{ $city->localizedName() }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-end gap-2">
        <button type="submit" class="h-10 flex-1 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white">{{ __('admin.filter') }}</button>
        <a href="{{ route('admin.events.calendar', ['year' => $cursor->year, 'month' => $cursor->month]) }}"
           class="inline-flex h-10 items-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600 no-underline hover:border-indigo-300">
            {{ __('admin.clear') }}
        </a>
    </div>
</form>

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.events.calendar', array_merge($filterQuery, ['year' => $prev->year, 'month' => $prev->month])) }}"
           class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 no-underline hover:border-indigo-300 hover:text-indigo-700"
           aria-label="{{ __('admin.prev_month') }}">
            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M15 18l-6-6 6-6"/></svg>
        </a>
        <h2 class="min-w-[10rem] text-center text-lg font-extrabold text-slate-900">
            {{ $cursor->translatedFormat('F Y') }}
        </h2>
        <a href="{{ route('admin.events.calendar', array_merge($filterQuery, ['year' => $next->year, 'month' => $next->month])) }}"
           class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 no-underline hover:border-indigo-300 hover:text-indigo-700"
           aria-label="{{ __('admin.next_month') }}">
            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 18l6-6-6-6"/></svg>
        </a>
    </div>
    <a href="{{ route('admin.events.calendar', array_merge($filterQuery, ['year' => now()->year, 'month' => now()->month])) }}"
       class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 no-underline hover:border-indigo-300">
        {{ __('admin.today') }}
    </a>
</div>

<div class="overflow-x-auto rounded-[18px] border border-[#e7ebf3] bg-white shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
    <div class="grid min-w-[720px] grid-cols-7 border-b border-slate-100 bg-slate-50">
        @foreach ($dayNames as $dayName)
            <div class="px-2 py-2.5 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $dayName }}</div>
        @endforeach
    </div>

    <div class="grid min-w-[720px] grid-cols-7">
        @foreach ($weeks as $week)
            @foreach ($week as $cell)
                <div @class([
                    'min-h-[7.5rem] border-b border-e border-slate-100 p-1.5',
                    'bg-white' => $cell['in_month'],
                    'bg-slate-50/80' => ! $cell['in_month'],
                ])>
                    <div class="mb-1 flex items-center justify-between px-0.5">
                        <span @class([
                            'inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold',
                            'bg-indigo-600 text-white' => $cell['is_today'],
                            'text-slate-800' => $cell['in_month'] && ! $cell['is_today'],
                            'text-slate-400' => ! $cell['in_month'],
                        ])>{{ $cell['date']->day }}</span>
                        @if ($cell['events']->isNotEmpty())
                            <span class="text-[10px] font-semibold text-slate-400">{{ $cell['events']->count() }}</span>
                        @endif
                    </div>
                    <div class="space-y-1">
                        @foreach ($cell['events']->take(3) as $event)
                            <a href="{{ route('events.show', $event) }}"
                               @class([
                                   'block truncate rounded-lg px-1.5 py-1 text-[10px] font-semibold no-underline leading-tight',
                                   $statusColors[$event->status->value] ?? 'bg-slate-100 text-slate-700',
                               ])
                               title="{{ $event->title }} · {{ $event->starts_at->timezone(config('app.timezone'))->format('H:i') }}">
                                {{ $event->starts_at->timezone(config('app.timezone'))->format('H:i') }} {{ $event->title }}
                            </a>
                        @endforeach
                        @if ($cell['events']->count() > 3)
                            <p class="px-1 text-[10px] font-semibold text-slate-400">+{{ $cell['events']->count() - 3 }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
</div>
@endsection
