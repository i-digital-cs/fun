@extends('layouts.admin')

@section('title', __('admin.today_metric_'.$metric).' — '.__('admin.activity_rows'))
@section('topbar_title', __('admin.activity_rows'))

@section('content')
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-xs font-bold uppercase tracking-wide text-indigo-500">
            <a href="{{ route('admin.dashboard') }}" class="text-indigo-500 no-underline hover:underline">{{ __('admin.dashboard') }}</a>
            <span class="mx-1 text-slate-300">/</span>
            {{ __('admin.activity_rows') }}
        </p>
        <h1 class="mt-1 text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.today_metric_'.$metric) }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            {{ __('admin.activity_rows_sub', ['day' => $dayLabel, 'count' => number_format($total)]) }}
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach (['today' => __('admin.activity_day_today'), 'yesterday' => __('admin.activity_day_yesterday')] as $dayKey => $dayName)
            <a href="{{ route('admin.activity.show', ['metric' => $metric, 'day' => $dayKey]) }}"
               @class([
                   'rounded-full px-4 py-2 text-sm font-semibold no-underline transition',
                   'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-600/25' => $day === $dayKey,
                   'border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-700' => $day !== $dayKey,
               ])>
                {{ $dayName }}
            </a>
        @endforeach
    </div>
</div>

<div class="mb-5 flex flex-wrap gap-2">
    @foreach ($metricKeys as $key)
        <a href="{{ route('admin.activity.show', ['metric' => $key, 'day' => $day]) }}"
           @class([
               'rounded-full px-3.5 py-1.5 text-xs font-semibold no-underline transition sm:text-sm',
               'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' => $metric === $key,
               'border border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:text-indigo-700' => $metric !== $key,
           ])>
            {{ __('admin.today_metric_'.$key) }}
            <span @class([
                'ms-1 rounded-full px-1.5 py-0.5 text-[10px] font-bold',
                'bg-white/20 text-white' => $metric === $key,
                'bg-slate-100 text-slate-500' => $metric !== $key,
            ])>{{ number_format((int) ($counts[$key] ?? 0)) }}</span>
        </a>
    @endforeach
</div>

<div class="overflow-hidden rounded-[18px] border border-[#e7ebf3] bg-white shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
    <div class="overflow-x-auto">
        <table class="min-w-full text-start text-sm">
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                @include('admin.activity._headers', ['metric' => $metric])
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    @include('admin.activity._row', ['metric' => $metric, 'row' => $row])
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                            {{ __('admin.activity_empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endsection
