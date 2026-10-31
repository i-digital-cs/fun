@extends('layouts.admin')

@section('title', __('admin.dashboard_command'))
@section('topbar_title', __('admin.dashboard_command'))

@section('content')
@php
    $range = $period['preset'] ?? '7d';
@endphp

{{-- A. Executive overview --}}
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div class="min-w-0">
        <p class="text-[11px] font-bold uppercase tracking-widest text-[#7138F5]">{{ __('admin.panel') }}</p>
        <h1 class="mt-1 text-[26px] font-extrabold tracking-tight text-[#19213d]">{{ __('admin.dashboard_command') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('admin.dashboard_command_sub', ['brand' => $siteName ?? site_display_name()]) }}</p>
        <p class="mt-2 text-[11px] text-slate-400">
            {{ __('admin.last_updated', ['time' => $generatedAt->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
            · {{ $period['timezone'] }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.reports.index') }}"
           class="inline-flex items-center rounded-xl border border-[#e7ebf3] bg-white px-3 py-2 text-xs font-bold text-slate-700 no-underline shadow-sm transition hover:border-[#7138F5]/30 hover:text-[#7138F5]">
            {{ __('admin.reports') }}
        </a>
        <a href="{{ route('admin.events.index') }}"
           class="inline-flex items-center rounded-xl border border-[#e7ebf3] bg-white px-3 py-2 text-xs font-bold text-slate-700 no-underline shadow-sm transition hover:border-[#7138F5]/30 hover:text-[#7138F5]">
            {{ __('admin.events') }}
        </a>
        <a href="{{ request()->fullUrlWithQuery([]) }}"
           class="inline-flex items-center rounded-xl bg-gradient-to-r from-[#FF762E] to-amber-500 px-3 py-2 text-xs font-extrabold text-white no-underline shadow-sm shadow-orange-500/25 transition hover:brightness-105">
            {{ __('admin.refresh') }}
        </a>
    </div>
</div>

<form method="get" action="{{ route('admin.dashboard') }}" class="mb-8 rounded-2xl border border-[#e7ebf3] bg-white p-3 shadow-[0_10px_30px_rgba(25,33,61,0.04)] sm:p-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.reporting_period') }}</p>
            <p class="mt-1 text-sm font-semibold text-[#19213d]">{{ $period['label'] }}</p>
        </div>
        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('admin.reporting_period') }}">
            @foreach (['today' => __('admin.range_today'), '7d' => __('admin.range_7d'), '30d' => __('admin.range_30d'), '90d' => __('admin.range_90d')] as $key => $label)
                <a href="{{ route('admin.dashboard', ['range' => $key]) }}"
                   @class([
                       'rounded-xl px-3 py-2 text-xs font-bold no-underline transition',
                       'bg-[#7138F5] text-white shadow-sm shadow-[#7138F5]/25' => $range === $key,
                       'bg-[#F8F9FE] text-slate-600 hover:bg-indigo-50 hover:text-[#7138F5]' => $range !== $key,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-end gap-2 border-t border-[#eef1f7] pt-3">
        <div>
            <label for="dash-from" class="mb-1 block text-[11px] font-bold text-slate-500">{{ __('admin.range_from') }}</label>
            <input id="dash-from" type="date" name="from" value="{{ request('from', $period['from']) }}"
                   class="rounded-xl border border-[#e7ebf3] bg-[#F8F9FE] px-3 py-2 text-sm text-slate-800 focus:border-[#7138F5] focus:outline-none focus:ring-4 focus:ring-[#7138F5]/15">
        </div>
        <div>
            <label for="dash-to" class="mb-1 block text-[11px] font-bold text-slate-500">{{ __('admin.range_to') }}</label>
            <input id="dash-to" type="date" name="to" value="{{ request('to', $period['to']) }}"
                   class="rounded-xl border border-[#e7ebf3] bg-[#F8F9FE] px-3 py-2 text-sm text-slate-800 focus:border-[#7138F5] focus:outline-none focus:ring-4 focus:ring-[#7138F5]/15">
        </div>
        <input type="hidden" name="range" value="custom">
        <button type="submit" class="rounded-xl border border-[#e7ebf3] bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-[#7138F5]/30 hover:text-[#7138F5]">
            {{ __('admin.apply_custom_range') }}
        </button>
    </div>
</form>

<x-admin.dashboard.section :title="__('admin.section_executive')" :subtitle="__('admin.section_executive_sub')" id="executive">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as $kpi)
            <x-admin.dashboard.kpi-card :kpi="$kpi" />
        @endforeach
    </div>
</x-admin.dashboard.section>

{{-- B. Action center --}}
<x-admin.dashboard.section :title="__('admin.section_action')" :subtitle="__('admin.section_action_sub')" id="action-center">
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @forelse ($actionCenter as $item)
            <x-admin.dashboard.action-item :item="$item" />
        @empty
            <div class="rounded-2xl border border-dashed border-[#e7ebf3] bg-white p-6 text-sm text-slate-500 md:col-span-2 xl:col-span-4">
                {{ __('admin.action_empty') }}
            </div>
        @endforelse
    </div>
</x-admin.dashboard.section>

<div class="grid gap-6 xl:grid-cols-5">
    {{-- C. Activity --}}
    <div class="xl:col-span-2">
        <x-admin.dashboard.section :title="__('admin.section_activity')" :subtitle="__('admin.section_activity_sub')" id="activity">
            <div class="overflow-hidden rounded-2xl border border-[#e7ebf3] bg-white shadow-[0_10px_30px_rgba(25,33,61,0.04)]">
                <ul class="divide-y divide-[#eef1f7]">
                    @forelse ($activityFeed as $row)
                        <li>
                            <a href="{{ $row['href'] }}" class="flex items-start gap-3 px-4 py-3 no-underline transition hover:bg-[#F8F9FE]">
                                @php
                                    $typeBadge = match ($row['type'] ?? '') {
                                        'user_registered' => 'UR',
                                        'event_created' => 'EV',
                                        'report_submitted' => 'RP',
                                        'friend_accepted' => 'FR',
                                        'admin_audit' => 'AD',
                                        default => '•',
                                    };
                                @endphp
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F8F9FE] text-[10px] font-extrabold uppercase tracking-wide text-[#7138F5]" aria-hidden="true">
                                    {{ $typeBadge }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs font-bold text-slate-500">{{ __('admin.activity_type_'.$row['type']) }}</span>
                                    <span class="mt-0.5 block truncate text-sm font-semibold text-[#19213d]">{{ $row['title'] }}</span>
                                    @if ($row['meta'])
                                        <span class="mt-0.5 block truncate text-[11px] text-slate-400">{{ $row['meta'] }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-end">
                                    <time class="block text-[10px] font-semibold text-slate-400" datetime="{{ $row['at']?->toIso8601String() }}">
                                        {{ $row['at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    </time>
                                    <span class="mt-1 inline-block rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-500">{{ $row['status'] }}</span>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('admin.activity_feed_empty') }}</li>
                    @endforelse
                </ul>
            </div>
        </x-admin.dashboard.section>
    </div>

    {{-- D. Events ops summary + status --}}
    <div class="xl:col-span-3">
        <x-admin.dashboard.section :title="__('admin.section_events')" :subtitle="__('admin.section_events_sub')" id="events">
            <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'published' => $eventStatus['published'] ?? 0,
                    'draft' => $eventStatus['draft'] ?? 0,
                    'completed' => $eventStatus['completed'] ?? 0,
                    'cancelled' => $eventStatus['cancelled'] ?? 0,
                ] as $statusKey => $count)
                    <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('events.status_'.$statusKey) }}</p>
                        <p class="mt-2 text-2xl font-extrabold text-[#19213d]">{{ number_format($count) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mb-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                    <p class="text-[11px] font-bold text-slate-500">{{ __('admin.ops_created_period') }}</p>
                    <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($eventOps['created_period']) }}</p>
                </div>
                <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                    <p class="text-[11px] font-bold text-slate-500">{{ __('admin.ops_participants_period') }}</p>
                    <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($eventOps['participants_period']) }}</p>
                </div>
                <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                    <p class="text-[11px] font-bold text-slate-500">{{ __('admin.ops_joins_accepted_period') }}</p>
                    <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($eventOps['joins_accepted_period']) }}</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]">
                    <h3 class="text-sm font-extrabold text-[#19213d]">{{ __('admin.chart_event_creation') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_event_creation_sub') }}</p>
                    <div class="mt-3 h-56">
                        <canvas id="admin-events-trend-chart" aria-label="{{ __('admin.chart_event_creation') }}"></canvas>
                    </div>
                </div>
                <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]">
                    <h3 class="text-sm font-extrabold text-[#19213d]">{{ __('admin.chart_event_status') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_event_status_sub') }}</p>
                    <div class="mx-auto mt-3 h-56 max-w-xs">
                        <canvas id="admin-event-status-chart" aria-label="{{ __('admin.chart_event_status') }}"></canvas>
                    </div>
                </div>
            </div>
        </x-admin.dashboard.section>
    </div>
</div>

{{-- Recent events table --}}
<x-admin.dashboard.section :title="__('admin.section_recent_events')" :subtitle="__('admin.section_recent_events_sub')" id="recent-events">
    <div class="overflow-x-auto rounded-2xl border border-[#e7ebf3] bg-white shadow-[0_10px_30px_rgba(25,33,61,0.04)]">
        <table class="min-w-full text-start text-sm">
            <thead class="bg-[#F8F9FE] text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_event') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_creator') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_city') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_starts') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_status') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_participants') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.col_action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#eef1f7]">
                @forelse ($recentEvents as $event)
                    <tr class="hover:bg-[#F8F9FE]/80">
                        <td class="max-w-[14rem] truncate px-4 py-3 font-semibold text-[#19213d]">{{ $event['title'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $event['organizer'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $event['city'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $event['starts_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $event['status'] }}</span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-[#19213d]">{{ number_format($event['members_count']) }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ $event['href'] }}" class="text-xs font-bold text-[#7138F5] no-underline hover:underline">{{ __('admin.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">{{ __('admin.recent_events_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.dashboard.section>

{{-- E + F Social & Users --}}
<div class="grid gap-6 xl:grid-cols-2">
    <x-admin.dashboard.section :title="__('admin.section_social')" :subtitle="__('admin.section_social_sub')" id="social">
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'friend_requests' => $social['period']['friend_requests'],
                'friend_accepts' => $social['period']['friend_accepts'],
                'event_messages' => $social['period']['event_messages'],
                'direct_messages' => $social['period']['direct_messages'],
            ] as $key => $value)
                @php $d = $social['period']['deltas'][$key] ?? ['value' => 0, 'percent' => null]; @endphp
                <a href="{{ route('admin.activity.show', ['metric' => $key === 'friend_accepts' ? 'friend_accepts' : ($key === 'friend_requests' ? 'friend_requests' : $key), 'day' => 'today']) }}"
                   class="rounded-2xl border border-[#e7ebf3] bg-white p-4 no-underline shadow-[0_8px_24px_rgba(25,33,61,0.03)] transition hover:border-[#7138F5]/25">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#7138F5]/90">{{ __('admin.today_metric_'.$key) }}</p>
                    <p class="mt-2 text-2xl font-extrabold text-[#19213d]">{{ number_format($value) }}</p>
                    <p class="mt-1 text-[11px] font-semibold {{ ($d['value'] ?? 0) > 0 ? 'text-emerald-600' : (($d['value'] ?? 0) < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                        {{ ($d['value'] ?? 0) > 0 ? '+' : '' }}{{ number_format($d['value'] ?? 0) }}
                        {{ __('admin.kpi_vs_previous') }}
                    </p>
                </a>
            @endforeach
        </div>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.social_accepted_lifetime') }}</p>
                <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($social['lifetime']['accepted_friendships']) }}</p>
            </div>
            <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.social_pending_lifetime') }}</p>
                <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($social['lifetime']['pending_friendships']) }}</p>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-slate-400">{{ __('admin.social_privacy_note') }}</p>
        <div class="mt-4 h-56 rounded-2xl border border-[#e7ebf3] bg-white p-4">
            <canvas id="admin-actions-chart" aria-label="{{ __('admin.chart_actions') }}"></canvas>
        </div>
    </x-admin.dashboard.section>

    <x-admin.dashboard.section :title="__('admin.section_users')" :subtitle="__('admin.section_users_sub')" id="users">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.status_active') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['by_status']['active']) }}</p>
                <p class="mt-1 text-[10px] text-slate-400">{{ __('admin.kpi_hint_active_accounts') }}</p>
            </div>
            <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.status_pending') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['by_status']['pending']) }}</p>
            </div>
            <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.status_deactivated') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['by_status']['deactivated']) }}</p>
            </div>
        </div>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.users_creators_period') }}</p>
                <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['creators_period']) }}</p>
            </div>
            <div class="rounded-2xl border border-[#e7ebf3] bg-[#F8F9FE] p-4">
                <p class="text-[11px] font-bold text-slate-500">{{ __('admin.users_joiners_period') }}</p>
                <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['joiners_period']) }}</p>
            </div>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4">
                <h3 class="text-sm font-extrabold text-[#19213d]">{{ __('admin.chart_activity_period', ['days' => $period['days']]) }}</h3>
                <div class="mt-3 h-52">
                    <canvas id="admin-activity-chart" aria-label="{{ __('admin.chart_activity_period', ['days' => $period['days']]) }}"></canvas>
                </div>
            </div>
            <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4">
                <h3 class="text-sm font-extrabold text-[#19213d]">{{ __('admin.chart_users') }}</h3>
                <div class="mx-auto mt-3 h-52 max-w-xs">
                    <canvas id="admin-users-chart" aria-label="{{ __('admin.chart_users') }}"></canvas>
                </div>
            </div>
        </div>
    </x-admin.dashboard.section>
</div>

{{-- G. Geographic --}}
<x-admin.dashboard.section :title="__('admin.section_geo')" :subtitle="__('admin.section_geo_sub')" id="geo">
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]">
            <h3 class="text-sm font-extrabold text-[#19213d]">{{ __('admin.geo_events_title') }}</h3>
            <div class="mt-3 h-56">
                <canvas id="admin-geo-chart" aria-label="{{ __('admin.geo_events_title') }}"></canvas>
            </div>
            @if (count($geo['cities_by_events'] ?? []) === 0)
                <p class="mt-2 text-center text-xs text-slate-400">{{ __('admin.geo_empty') }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]">
            <h3 class="text-sm font-extrabold text-[#19213d]">{{ __('admin.geo_participants_title') }}</h3>
            <ul class="mt-3 divide-y divide-[#eef1f7]">
                @forelse ($geo['cities_by_participants'] as $row)
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <span class="font-semibold text-[#19213d]">{{ $row['label'] }}</span>
                        <span class="font-bold text-[#7138F5]">{{ number_format($row['count']) }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-xs text-slate-400">{{ __('admin.geo_empty') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin.dashboard.section>

{{-- Categories chart --}}
<x-admin.dashboard.section :title="__('admin.chart_categories')" :subtitle="__('admin.chart_categories_sub')" id="categories">
    <div class="rounded-2xl border border-[#e7ebf3] bg-white p-4 shadow-[0_8px_24px_rgba(25,33,61,0.03)]">
        <div class="h-64">
            <canvas id="admin-categories-chart" aria-label="{{ __('admin.chart_categories') }}"></canvas>
        </div>
    </div>
</x-admin.dashboard.section>

{{-- H. System health --}}
<x-admin.dashboard.section :title="__('admin.section_health')" :subtitle="__('admin.section_health_sub')" id="health">
    <div class="grid gap-3 md:grid-cols-2">
        <div @class([
            'rounded-2xl border p-5 shadow-[0_8px_24px_rgba(25,33,61,0.03)]',
            'border-rose-200 bg-rose-50' => ($health['failed_jobs'] ?? 0) > 0,
            'border-[#e7ebf3] bg-white' => ($health['failed_jobs'] ?? 0) === 0,
        ])>
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('admin.health_failed_jobs') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-[#19213d]">{{ number_format($health['failed_jobs'] ?? 0) }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ __('admin.health_failed_jobs_hint') }}</p>
        </div>
        <div class="rounded-2xl border border-dashed border-[#e7ebf3] bg-[#F8F9FE] p-5">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('admin.health_monitoring') }}</p>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $health['monitoring_note'] }}</p>
        </div>
    </div>
</x-admin.dashboard.section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        Chart.defaults.animation = false;
    }

    const charts = @json($charts);
    const grid = 'rgba(148, 163, 184, 0.2)';
    const ink = '#64748b';
    const brand = '#7138F5';
    const accent = '#FF762E';

    const lineOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, color: ink } } },
        scales: {
            x: { ticks: { color: ink, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { color: grid } },
            y: { beginAtZero: true, ticks: { precision: 0, color: ink }, grid: { color: grid } },
        },
    };

    const el = (id) => document.getElementById(id);

    if (el('admin-activity-chart')) {
        new Chart(el('admin-activity-chart'), {
            type: 'line',
            data: {
                labels: charts.days,
                datasets: [
                    { label: @json(__('admin.chart_series_events')), data: charts.events_per_day, borderColor: brand, backgroundColor: 'rgba(113,56,245,0.12)', fill: true, tension: 0.35 },
                    { label: @json(__('admin.chart_series_users')), data: charts.users_per_day, borderColor: accent, backgroundColor: 'rgba(255,118,46,0.12)', fill: true, tension: 0.35 },
                ],
            },
            options: lineOpts,
        });
    }

    if (el('admin-events-trend-chart')) {
        new Chart(el('admin-events-trend-chart'), {
            type: 'line',
            data: {
                labels: charts.days,
                datasets: [
                    { label: @json(__('admin.chart_series_events')), data: charts.events_per_day, borderColor: brand, backgroundColor: 'rgba(113,56,245,0.10)', fill: true, tension: 0.35 },
                    { label: @json(__('admin.chart_series_participants')), data: charts.participants_per_day, borderColor: accent, backgroundColor: 'rgba(255,118,46,0.08)', fill: false, tension: 0.35 },
                ],
            },
            options: lineOpts,
        });
    }

    if (el('admin-actions-chart')) {
        new Chart(el('admin-actions-chart'), {
            type: 'line',
            data: {
                labels: charts.days,
                datasets: [
                    { label: @json(__('admin.chart_series_join_requests')), data: charts.join_requests_per_day, borderColor: brand, fill: false, tension: 0.35 },
                    { label: @json(__('admin.chart_series_joins_accepted')), data: charts.joins_accepted_per_day, borderColor: '#159b76', fill: false, tension: 0.35 },
                    { label: @json(__('admin.chart_series_event_messages')), data: charts.event_messages_per_day, borderColor: '#F59E0B', fill: false, tension: 0.35 },
                    { label: @json(__('admin.chart_series_direct_messages')), data: charts.direct_messages_per_day, borderColor: '#EC4899', fill: false, tension: 0.35 },
                ],
            },
            options: lineOpts,
        });
    }

    if (el('admin-event-status-chart')) {
        new Chart(el('admin-event-status-chart'), {
            type: 'doughnut',
            data: {
                labels: charts.event_status_labels,
                datasets: [{ data: charts.event_status_values, backgroundColor: ['#94a3b8', brand, '#ef4444', '#159b76'], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, color: ink } } } },
        });
    }

    if (el('admin-categories-chart')) {
        new Chart(el('admin-categories-chart'), {
            type: 'bar',
            data: {
                labels: charts.category_labels,
                datasets: [{ label: @json(__('admin.chart_series_events')), data: charts.category_values, backgroundColor: brand, borderRadius: 8 }],
            },
            options: lineOpts,
        });
    }

    if (el('admin-users-chart')) {
        new Chart(el('admin-users-chart'), {
            type: 'doughnut',
            data: {
                labels: charts.user_status_labels,
                datasets: [{ data: charts.user_status_values, backgroundColor: [brand, '#F59E0B', '#94a3b8'], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, color: ink } } } },
        });
    }

    if (el('admin-geo-chart') && (charts.geo_event_labels || []).length) {
        new Chart(el('admin-geo-chart'), {
            type: 'bar',
            data: {
                labels: charts.geo_event_labels,
                datasets: [{ label: @json(__('admin.chart_series_events')), data: charts.geo_event_values, backgroundColor: accent, borderRadius: 8 }],
            },
            options: { ...lineOpts, indexAxis: 'y' },
        });
    }
});
</script>
@endpush
