@extends('layouts.admin')

@section('title', __('admin.dashboard'))
@section('topbar_title', __('admin.dashboard'))

@php
    $todayKeys = [
        'active_members',
        'registrations',
        'events_created',
        'join_requests',
        'joins_accepted',
        'event_messages',
        'direct_messages',
        'friend_requests',
        'friend_accepts',
    ];
@endphp

@section('content')
<div class="mb-6">
    <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.dashboard') }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ __('admin.dashboard_sub', ['brand' => $siteName ?? site_display_name()]) }}</p>
</div>

<section class="mb-8" aria-labelledby="admin-today-heading">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 id="admin-today-heading" class="text-base font-extrabold tracking-tight text-slate-900">{{ __('admin.today_activity') }}</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.today_activity_sub', ['tz' => config('app.timezone')]) }}</p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-3">
        @foreach ($todayKeys as $key)
            @php
                $value = (int) ($today[$key] ?? 0);
                $delta = $deltas[$key] ?? ['value' => 0, 'percent' => null];
                $deltaValue = (int) ($delta['value'] ?? 0);
                $deltaPercent = $delta['percent'] ?? null;
                $up = $deltaValue > 0;
                $down = $deltaValue < 0;
            @endphp
            <a href="{{ route('admin.activity.show', ['metric' => $key, 'day' => 'today']) }}"
               class="group block rounded-[18px] border border-[#e7ebf3] bg-white p-4 shadow-[0_12px_35px_rgba(15,23,42,0.04)] no-underline transition hover:border-indigo-200 hover:shadow-[0_16px_40px_rgba(79,70,229,0.08)] focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-100">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-500/90">{{ __('admin.today_metric_'.$key) }}</p>
                    <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 opacity-0 transition group-hover:opacity-100">{{ __('admin.activity_view_rows') }}</span>
                </div>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($value) }}</p>
                <p class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold
                    {{ $up ? 'text-emerald-600' : ($down ? 'text-rose-600' : 'text-slate-400') }}">
                    <span>
                        @if ($up)
                            +{{ number_format($deltaValue) }}
                        @elseif ($down)
                            {{ number_format($deltaValue) }}
                        @else
                            0
                        @endif
                        @if ($deltaPercent !== null)
                            ({{ $deltaPercent > 0 ? '+' : '' }}{{ $deltaPercent }}%)
                        @endif
                    </span>
                    <span class="font-medium text-slate-400">{{ __('admin.today_vs_yesterday') }}</span>
                </p>
                @if ($key === 'active_members')
                    <p class="mt-2 text-[10px] leading-snug text-slate-400">{{ __('admin.today_active_members_hint') }}</p>
                @endif
            </a>
        @endforeach
    </div>
</section>

<section class="mb-8" aria-labelledby="admin-totals-heading">
    <div class="mb-3">
        <h2 id="admin-totals-heading" class="text-base font-extrabold tracking-tight text-slate-900">{{ __('admin.totals_section') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.totals_section_sub') }}</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach ($stats as $key => $value)
            <div class="rounded-[18px] border border-[#e7ebf3] bg-white p-4 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.stat_'.$key) }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>
</section>

<div class="mt-6 grid gap-4 xl:grid-cols-2">
    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
        <h2 class="text-sm font-extrabold text-slate-900">{{ __('admin.chart_activity') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_activity_sub') }}</p>
        <div class="mt-4 h-64">
            <canvas id="admin-activity-chart" aria-label="{{ __('admin.chart_activity') }}"></canvas>
        </div>
    </section>

    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
        <h2 class="text-sm font-extrabold text-slate-900">{{ __('admin.chart_actions') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_actions_sub') }}</p>
        <div class="mt-4 h-64">
            <canvas id="admin-actions-chart" aria-label="{{ __('admin.chart_actions') }}"></canvas>
        </div>
    </section>

    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
        <h2 class="text-sm font-extrabold text-slate-900">{{ __('admin.chart_event_status') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_event_status_sub') }}</p>
        <div class="mx-auto mt-4 h-64 max-w-sm">
            <canvas id="admin-event-status-chart" aria-label="{{ __('admin.chart_event_status') }}"></canvas>
        </div>
    </section>

    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
        <h2 class="text-sm font-extrabold text-slate-900">{{ __('admin.chart_categories') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_categories_sub') }}</p>
        <div class="mt-4 h-64">
            <canvas id="admin-categories-chart" aria-label="{{ __('admin.chart_categories') }}"></canvas>
        </div>
    </section>

    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)] xl:col-span-2">
        <h2 class="text-sm font-extrabold text-slate-900">{{ __('admin.chart_users') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_users_sub') }}</p>
        <div class="mx-auto mt-4 h-64 max-w-sm">
            <canvas id="admin-users-chart" aria-label="{{ __('admin.chart_users') }}"></canvas>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;

    const charts = @json($charts);
    const grid = 'rgba(148, 163, 184, 0.2)';
    const ink = '#64748b';

    const lineOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, color: ink } } },
        scales: {
            x: { ticks: { color: ink, maxRotation: 0 }, grid: { color: grid } },
            y: { beginAtZero: true, ticks: { precision: 0, color: ink }, grid: { color: grid } },
        },
    };

    new Chart(document.getElementById('admin-activity-chart'), {
        type: 'line',
        data: {
            labels: charts.days,
            datasets: [
                {
                    label: @json(__('admin.chart_series_events')),
                    data: charts.events_per_day,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: @json(__('admin.chart_series_users')),
                    data: charts.users_per_day,
                    borderColor: '#F97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.12)',
                    fill: true,
                    tension: 0.35,
                },
            ],
        },
        options: lineOpts,
    });

    new Chart(document.getElementById('admin-actions-chart'), {
        type: 'line',
        data: {
            labels: charts.days,
            datasets: [
                {
                    label: @json(__('admin.chart_series_join_requests')),
                    data: charts.join_requests_per_day,
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: false,
                    tension: 0.35,
                },
                {
                    label: @json(__('admin.chart_series_joins_accepted')),
                    data: charts.joins_accepted_per_day,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: false,
                    tension: 0.35,
                },
                {
                    label: @json(__('admin.chart_series_event_messages')),
                    data: charts.event_messages_per_day,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: false,
                    tension: 0.35,
                },
                {
                    label: @json(__('admin.chart_series_direct_messages')),
                    data: charts.direct_messages_per_day,
                    borderColor: '#EC4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    fill: false,
                    tension: 0.35,
                },
            ],
        },
        options: lineOpts,
    });

    new Chart(document.getElementById('admin-event-status-chart'), {
        type: 'doughnut',
        data: {
            labels: charts.event_status_labels,
            datasets: [{
                data: charts.event_status_values,
                backgroundColor: ['#94a3b8', '#4F46E5', '#ef4444', '#10B981'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, color: ink } } },
        },
    });

    new Chart(document.getElementById('admin-categories-chart'), {
        type: 'bar',
        data: {
            labels: charts.category_labels,
            datasets: [{
                label: @json(__('admin.chart_series_events')),
                data: charts.category_values,
                backgroundColor: '#4F46E5',
                borderRadius: 8,
            }],
        },
        options: lineOpts,
    });

    new Chart(document.getElementById('admin-users-chart'), {
        type: 'doughnut',
        data: {
            labels: charts.user_status_labels,
            datasets: [{
                data: charts.user_status_values,
                backgroundColor: ['#4F46E5', '#F59E0B', '#94a3b8'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, color: ink } } },
        },
    });
});
</script>
@endpush
