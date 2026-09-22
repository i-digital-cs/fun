@extends('layouts.admin')

@section('title', __('admin.dashboard'))
@section('topbar_title', __('admin.dashboard'))

@section('content')
<div class="mb-6">
    <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.dashboard') }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ __('admin.dashboard_sub', ['brand' => $siteName ?? site_display_name()]) }}</p>
</div>

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
    @foreach ($stats as $key => $value)
        <div class="rounded-[18px] border border-[#e7ebf3] bg-white p-4 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.stat_'.$key) }}</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($value) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-4 xl:grid-cols-2">
    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
        <h2 class="text-sm font-extrabold text-slate-900">{{ __('admin.chart_activity') }}</h2>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_activity_sub') }}</p>
        <div class="mt-4 h-64">
            <canvas id="admin-activity-chart" aria-label="{{ __('admin.chart_activity') }}"></canvas>
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

    <section class="rounded-[20px] border border-[#e7ebf3] bg-white p-5 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
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
                backgroundColor: '#6366F1',
                borderRadius: 8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: ink }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0, color: ink }, grid: { color: grid } },
            },
        },
    });

    new Chart(document.getElementById('admin-users-chart'), {
        type: 'doughnut',
        data: {
            labels: charts.user_status_labels,
            datasets: [{
                data: charts.user_status_values,
                backgroundColor: ['#10B981', '#F59E0B', '#94a3b8'],
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
