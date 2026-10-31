@extends('layouts.admin')

@section('title', __('admin.dashboard_command'))
@section('topbar_title', __('admin.dashboard_command'))

@section('content')
@php
    $range = $period['preset'] ?? '7d';
    $primaryKeys = ['total_users', 'new_users', 'published_events', 'participants', 'events_created', 'session_active'];
    $secondaryKeys = ['active_accounts', 'pending_reports'];
    $kpisByKey = collect($kpis)->keyBy('key');
    $primaryKpis = collect($primaryKeys)->map(fn ($k) => $kpisByKey->get($k))->filter()->values();
    $secondaryKpis = collect($secondaryKeys)->map(fn ($k) => $kpisByKey->get($k))->filter()->values();
    $activityRows = array_slice($activityFeed ?? [], 0, 8);
@endphp

{{-- A. Command header + period toolbar --}}
<header class="mb-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-[1.65rem] font-extrabold tracking-tight text-[#19213d] sm:text-[1.85rem]">{{ __('admin.dashboard_command') }}</h1>
            <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600">{{ __('admin.dashboard_v21_sub', ['brand' => $siteName ?? site_display_name()]) }}</p>
            <p class="mt-2 text-xs text-slate-500">
                {{ __('admin.last_updated', ['time' => $generatedAt->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                · {{ $period['timezone'] }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.reports.index') }}"
               class="inline-flex min-h-10 items-center rounded-lg border border-[#e8eaf2] bg-white px-3 py-2 text-sm font-semibold text-slate-700 no-underline transition hover:border-[#7138F5]/35 hover:text-[#7138F5] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20">
                {{ __('admin.reports') }}
            </a>
            <a href="{{ route('admin.events.index') }}"
               class="inline-flex min-h-10 items-center rounded-lg border border-[#e8eaf2] bg-white px-3 py-2 text-sm font-semibold text-slate-700 no-underline transition hover:border-[#7138F5]/35 hover:text-[#7138F5] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20">
                {{ __('admin.events') }}
            </a>
            <a href="{{ url()->current().'?'.http_build_query(request()->except('_token')) }}"
               class="inline-flex min-h-10 items-center rounded-lg bg-[#FF762E] px-3 py-2 text-sm font-bold text-white no-underline shadow-sm transition hover:bg-[#e85f1a] focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-200">
                {{ __('admin.refresh') }}
            </a>
        </div>
    </div>

    <form method="get" action="{{ route('admin.dashboard') }}" class="admin-dash-toolbar mt-4 p-3 sm:p-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.reporting_period') }}</p>
                <p class="mt-0.5 text-sm font-semibold text-[#19213d]">{{ __('admin.command_header_range', ['label' => $period['label']]) }}</p>
            </div>
            <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('admin.reporting_period') }}">
                @foreach (['today' => __('admin.range_today'), '7d' => __('admin.range_7d'), '30d' => __('admin.range_30d'), '90d' => __('admin.range_90d')] as $key => $label)
                    <a href="{{ route('admin.dashboard', ['range' => $key]) }}"
                       @class([
                           'inline-flex min-h-9 items-center rounded-lg px-3 py-1.5 text-sm font-semibold no-underline transition focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20',
                           'bg-[#7138F5] text-white' => $range === $key,
                           'bg-[#F8F9FE] text-slate-600 hover:bg-[#7138F5]/10 hover:text-[#7138F5]' => $range !== $key,
                       ])>
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-end gap-2 border-t border-[#eef1f7] pt-3">
            <div>
                <label for="dash-from" class="mb-1 block text-xs font-semibold text-slate-500">{{ __('admin.range_from') }}</label>
                <input id="dash-from" type="date" name="from" value="{{ request('from', $period['from']) }}"
                       class="min-h-10 rounded-lg border border-[#e8eaf2] bg-[#F8F9FE] px-3 py-2 text-sm text-slate-800 focus:border-[#7138F5] focus:outline-none focus:ring-4 focus:ring-[#7138F5]/15">
            </div>
            <div>
                <label for="dash-to" class="mb-1 block text-xs font-semibold text-slate-500">{{ __('admin.range_to') }}</label>
                <input id="dash-to" type="date" name="to" value="{{ request('to', $period['to']) }}"
                       class="min-h-10 rounded-lg border border-[#e8eaf2] bg-[#F8F9FE] px-3 py-2 text-sm text-slate-800 focus:border-[#7138F5] focus:outline-none focus:ring-4 focus:ring-[#7138F5]/15">
            </div>
            <input type="hidden" name="range" value="custom">
            <button type="submit" class="min-h-10 rounded-lg border border-[#e8eaf2] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#7138F5]/35 hover:text-[#7138F5] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20">
                {{ __('admin.apply_custom_range') }}
            </button>
        </div>
    </form>
</header>

{{-- B. Executive overview --}}
<x-admin.dashboard.section :title="__('admin.section_executive')" :subtitle="__('admin.section_executive_sub')" id="executive">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($primaryKpis as $kpi)
            <x-admin.dashboard.kpi-card :kpi="$kpi" variant="primary" />
        @endforeach
    </div>
    @if ($secondaryKpis->isNotEmpty())
        <p class="mb-2 mt-4 text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('admin.kpi_secondary_title') }}</p>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($secondaryKpis as $kpi)
                <x-admin.dashboard.kpi-card :kpi="$kpi" variant="secondary" />
            @endforeach
        </div>
    @endif
</x-admin.dashboard.section>

{{-- C. Needs attention --}}
<x-admin.dashboard.section :title="__('admin.section_action')" :subtitle="__('admin.section_action_sub')" id="action-center">
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($actionCenter as $item)
            <x-admin.dashboard.action-item :item="$item" />
        @empty
            <div class="rounded-xl border border-dashed border-[#e8eaf2] bg-white p-6 text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                {{ __('admin.action_empty') }}
            </div>
        @endforelse
    </div>
</x-admin.dashboard.section>

{{-- D + E Activity + Events --}}
<div class="grid gap-6 xl:grid-cols-12">
    <div class="xl:col-span-4">
        <x-admin.dashboard.section :title="__('admin.section_activity')" :subtitle="__('admin.section_activity_sub')" id="activity">
            <x-slot:actions>
                <a href="{{ route('admin.activity.show', ['metric' => 'registrations', 'day' => 'today']) }}"
                   class="text-xs font-bold text-[#7138F5] no-underline hover:underline">{{ __('admin.activity_view_today') }}</a>
            </x-slot:actions>
            <div class="overflow-hidden rounded-xl border border-[#e8eaf2] bg-white shadow-sm">
                <ul class="divide-y divide-[#eef1f7]">
                    @forelse ($activityRows as $row)
                        <li>
                            <a href="{{ $row['href'] }}" class="admin-activity-row flex items-center gap-3 px-3 py-2.5 no-underline transition hover:bg-[#F8F9FE] focus:outline-none focus-visible:bg-[#F8F9FE]">
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
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F1EBFF] text-[10px] font-extrabold tracking-wide text-[#7138F5]" aria-hidden="true">{{ $typeBadge }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-xs font-semibold text-slate-500">{{ __('admin.activity_type_'.$row['type']) }}</span>
                                    <span class="mt-0.5 block truncate text-sm font-semibold text-[#19213d]">{{ $row['title'] }}</span>
                                </span>
                                <span class="shrink-0 text-end">
                                    <time class="block text-[11px] font-semibold text-slate-400" datetime="{{ $row['at']?->toIso8601String() }}">
                                        {{ $row['at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                    </time>
                                    <span class="mt-1 inline-block rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $row['status'] }}</span>
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

    <div class="xl:col-span-8">
        <x-admin.dashboard.section :title="__('admin.section_events')" :subtitle="__('admin.section_events_sub')" id="events">
            <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ([
                    'published' => $eventStatus['published'] ?? 0,
                    'draft' => $eventStatus['draft'] ?? 0,
                    'completed' => $eventStatus['completed'] ?? 0,
                    'cancelled' => $eventStatus['cancelled'] ?? 0,
                ] as $statusKey => $count)
                    <div class="rounded-xl border border-[#e8eaf2] bg-white px-3 py-3 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ __('events.status_'.$statusKey) }}</p>
                        <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($count) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mb-4 grid gap-2 sm:grid-cols-3">
                <div class="rounded-xl bg-[#F8F9FE] px-3 py-3 ring-1 ring-[#e8eaf2]">
                    <p class="text-xs font-semibold text-slate-500">{{ __('admin.ops_created_period') }}</p>
                    <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($eventOps['created_period']) }}</p>
                </div>
                <div class="rounded-xl bg-[#F8F9FE] px-3 py-3 ring-1 ring-[#e8eaf2]">
                    <p class="text-xs font-semibold text-slate-500">{{ __('admin.ops_participants_period') }}</p>
                    <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($eventOps['participants_period']) }}</p>
                </div>
                <div class="rounded-xl bg-[#F8F9FE] px-3 py-3 ring-1 ring-[#e8eaf2]">
                    <p class="text-xs font-semibold text-slate-500">{{ __('admin.ops_joins_accepted_period') }}</p>
                    <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($eventOps['joins_accepted_period']) }}</p>
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-5">
                <div class="admin-chart-panel p-4 lg:col-span-3">
                    <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.chart_event_creation') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_event_creation_sub') }}</p>
                    <div class="admin-chart-panel__canvas mt-3">
                        <canvas id="admin-events-trend-chart" aria-label="{{ __('admin.chart_event_creation') }}"></canvas>
                    </div>
                </div>
                <div class="admin-chart-panel p-4 lg:col-span-2">
                    <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.chart_event_status') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('admin.chart_event_status_sub') }}</p>
                    <div class="admin-chart-panel__canvas mx-auto mt-3 max-w-[220px]">
                        <canvas id="admin-event-status-chart" aria-label="{{ __('admin.chart_event_status') }}"></canvas>
                    </div>
                </div>
            </div>
        </x-admin.dashboard.section>
    </div>
</div>

{{-- Recent events --}}
<x-admin.dashboard.section :title="__('admin.section_recent_events')" :subtitle="__('admin.section_recent_events_sub')" id="recent-events">
    <div class="overflow-x-auto rounded-xl border border-[#e8eaf2] bg-white shadow-sm">
        <table class="min-w-full text-start text-sm">
            <thead class="bg-[#F8F9FE] text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2.5 text-start sm:px-4">{{ __('admin.col_event') }}</th>
                    <th class="hidden px-3 py-2.5 text-start sm:table-cell sm:px-4">{{ __('admin.col_creator') }}</th>
                    <th class="hidden px-3 py-2.5 text-start md:table-cell md:px-4">{{ __('admin.col_city') }}</th>
                    <th class="px-3 py-2.5 text-start sm:px-4">{{ __('admin.col_starts') }}</th>
                    <th class="px-3 py-2.5 text-start sm:px-4">{{ __('admin.col_status') }}</th>
                    <th class="px-3 py-2.5 text-start sm:px-4">{{ __('admin.col_participants') }}</th>
                    <th class="px-3 py-2.5 text-start sm:px-4">{{ __('admin.col_action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#eef1f7]">
                @forelse ($recentEvents as $event)
                    <tr class="hover:bg-[#F8F9FE]/80">
                        <td class="max-w-[12rem] truncate px-3 py-2.5 font-semibold text-[#19213d] sm:max-w-[16rem] sm:px-4">{{ $event['title'] }}</td>
                        <td class="hidden px-3 py-2.5 text-slate-600 sm:table-cell sm:px-4">{{ $event['organizer'] ?? '—' }}</td>
                        <td class="hidden px-3 py-2.5 text-slate-600 md:table-cell md:px-4">{{ $event['city'] ?? '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-2.5 text-slate-600 sm:px-4">{{ $event['starts_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2.5 sm:px-4">
                            <span class="rounded-md bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $event['status'] }}</span>
                        </td>
                        <td class="px-3 py-2.5 font-semibold text-[#19213d] sm:px-4">{{ number_format($event['members_count']) }}</td>
                        <td class="px-3 py-2.5 sm:px-4">
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

{{-- F. Users & social --}}
<x-admin.dashboard.section :title="__('admin.section_users_social')" :subtitle="__('admin.section_users_social_sub')" id="users-social">
    <div class="grid gap-4 xl:grid-cols-12">
        <div class="grid gap-2 sm:grid-cols-3 xl:col-span-4">
            <div class="rounded-xl border border-[#e8eaf2] bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.status_active') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['by_status']['active']) }}</p>
            </div>
            <div class="rounded-xl border border-[#e8eaf2] bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.status_pending') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['by_status']['pending']) }}</p>
            </div>
            <div class="rounded-xl border border-[#e8eaf2] bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.status_deactivated') }}</p>
                <p class="mt-1 text-2xl font-extrabold text-[#19213d]">{{ number_format($usersBlock['by_status']['deactivated']) }}</p>
            </div>
            <div class="rounded-xl bg-[#F8F9FE] p-3 ring-1 ring-[#e8eaf2] sm:col-span-1">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.users_creators_period') }}</p>
                <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($usersBlock['creators_period']) }}</p>
            </div>
            <div class="rounded-xl bg-[#F8F9FE] p-3 ring-1 ring-[#e8eaf2] sm:col-span-2">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.users_joiners_period') }}</p>
                <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($usersBlock['joiners_period']) }}</p>
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 xl:col-span-4">
            @foreach ([
                'friend_requests' => $social['period']['friend_requests'],
                'friend_accepts' => $social['period']['friend_accepts'],
                'event_messages' => $social['period']['event_messages'],
                'direct_messages' => $social['period']['direct_messages'],
            ] as $key => $value)
                @php $d = $social['period']['deltas'][$key] ?? ['value' => 0, 'percent' => null]; @endphp
                <a href="{{ route('admin.activity.show', ['metric' => $key, 'day' => 'today']) }}"
                   class="rounded-xl border border-[#e8eaf2] bg-white p-3 no-underline shadow-sm transition hover:border-[#7138F5]/30 focus:outline-none focus-visible:ring-4 focus-visible:ring-[#7138F5]/20">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-[#7138F5]">{{ __('admin.today_metric_'.$key) }}</p>
                    <p class="mt-1 text-xl font-extrabold text-[#19213d]">{{ number_format($value) }}</p>
                    <p class="mt-1 text-xs font-semibold {{ ($d['value'] ?? 0) > 0 ? 'text-emerald-600' : (($d['value'] ?? 0) < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                        {{ ($d['value'] ?? 0) > 0 ? '+' : '' }}{{ number_format($d['value'] ?? 0) }}
                        {{ __('admin.kpi_vs_previous') }}
                    </p>
                </a>
            @endforeach
            <div class="rounded-xl bg-[#F8F9FE] p-3 ring-1 ring-[#e8eaf2]">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.social_accepted_lifetime') }}</p>
                <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($social['lifetime']['accepted_friendships']) }}</p>
            </div>
            <div class="rounded-xl bg-[#F8F9FE] p-3 ring-1 ring-[#e8eaf2]">
                <p class="text-xs font-semibold text-slate-500">{{ __('admin.social_pending_lifetime') }}</p>
                <p class="mt-1 text-lg font-extrabold text-[#19213d]">{{ number_format($social['lifetime']['pending_friendships']) }}</p>
            </div>
            <p class="text-xs text-slate-400 sm:col-span-2">{{ __('admin.social_privacy_note') }}</p>
        </div>

        <div class="grid gap-3 xl:col-span-4">
            <div class="admin-chart-panel p-4">
                <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.chart_activity_period', ['days' => $period['days']]) }}</h3>
                <div class="admin-chart-panel__canvas mt-3">
                    <canvas id="admin-activity-chart" aria-label="{{ __('admin.chart_activity_period', ['days' => $period['days']]) }}"></canvas>
                </div>
            </div>
            <div class="admin-chart-panel p-4">
                <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.chart_actions') }}</h3>
                <div class="admin-chart-panel__canvas mt-3">
                    <canvas id="admin-actions-chart" aria-label="{{ __('admin.chart_actions') }}"></canvas>
                </div>
            </div>
        </div>
    </div>
</x-admin.dashboard.section>

{{-- G. Geographic + categories --}}
<div class="grid gap-6 xl:grid-cols-2">
    <x-admin.dashboard.section :title="__('admin.section_geo')" :subtitle="__('admin.section_geo_sub')" id="geo">
        <div class="admin-chart-panel p-4">
            <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.geo_events_title') }}</h3>
            <div class="admin-chart-panel__canvas mt-3">
                <canvas id="admin-geo-chart" aria-label="{{ __('admin.geo_events_title') }}"></canvas>
            </div>
            @if (count($geo['cities_by_events'] ?? []) === 0)
                <p class="mt-2 text-center text-xs text-slate-400">{{ __('admin.geo_empty') }}</p>
            @endif
        </div>
        <div class="mt-3 rounded-xl border border-[#e8eaf2] bg-white p-4 shadow-sm">
            <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.geo_participants_title') }}</h3>
            <ul class="mt-2 divide-y divide-[#eef1f7]">
                @forelse ($geo['cities_by_participants'] as $row)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <span class="font-semibold text-[#19213d]">{{ $row['label'] }}</span>
                        <span class="font-bold text-[#7138F5]">{{ number_format($row['count']) }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-xs text-slate-400">{{ __('admin.geo_empty') }}</li>
                @endforelse
            </ul>
        </div>
    </x-admin.dashboard.section>

    <x-admin.dashboard.section :title="__('admin.chart_categories')" :subtitle="__('admin.chart_categories_sub')" id="categories">
        <div class="admin-chart-panel p-4">
            <div class="admin-chart-panel__canvas" style="height: 16rem">
                <canvas id="admin-categories-chart" aria-label="{{ __('admin.chart_categories') }}"></canvas>
            </div>
        </div>
        <div class="admin-chart-panel mt-3 p-4">
            <h3 class="text-sm font-bold text-[#19213d]">{{ __('admin.chart_users') }}</h3>
            <div class="admin-chart-panel__canvas mx-auto mt-3 max-w-[220px]">
                <canvas id="admin-users-chart" aria-label="{{ __('admin.chart_users') }}"></canvas>
            </div>
        </div>
    </x-admin.dashboard.section>
</div>

{{-- H. System health --}}
<x-admin.dashboard.section :title="__('admin.section_health')" :subtitle="__('admin.section_health_sub')" id="health">
    <div class="grid gap-3 md:grid-cols-2">
        <div @class([
            'rounded-xl border p-4 shadow-sm',
            'border-rose-200 bg-rose-50' => ($health['failed_jobs'] ?? 0) > 0,
            'border-[#e8eaf2] bg-white' => ($health['failed_jobs'] ?? 0) === 0,
        ])>
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('admin.health_failed_jobs') }}</p>
            <p class="mt-2 text-3xl font-extrabold text-[#19213d]">{{ number_format($health['failed_jobs'] ?? 0) }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ __('admin.health_failed_jobs_hint') }}</p>
        </div>
        <div class="rounded-xl border border-dashed border-[#e8eaf2] bg-[#F8F9FE] p-4">
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
    const grid = 'rgba(148, 163, 184, 0.18)';
    const ink = '#64748b';
    const brand = '#7138F5';
    const accent = '#FF762E';

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = ink;

    const lineOpts = {
        responsive: true,
        maintainAspectRatio: false,
        resizeDelay: 0,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, color: ink, font: { size: 11 } } } },
        scales: {
            x: { ticks: { color: ink, maxRotation: 0, autoSkip: true, maxTicksLimit: 8, font: { size: 11 } }, grid: { color: grid } },
            y: { beginAtZero: true, ticks: { precision: 0, color: ink, font: { size: 11 } }, grid: { color: grid } },
        },
    };

    const doughnutOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, color: ink, font: { size: 11 } } } },
    };

    const el = (id) => document.getElementById(id);

    if (el('admin-activity-chart')) {
        new Chart(el('admin-activity-chart'), {
            type: 'line',
            data: {
                labels: charts.days,
                datasets: [
                    { label: @json(__('admin.chart_series_events')), data: charts.events_per_day, borderColor: brand, backgroundColor: 'rgba(113,56,245,0.10)', fill: true, tension: 0.3, borderWidth: 2 },
                    { label: @json(__('admin.chart_series_users')), data: charts.users_per_day, borderColor: accent, backgroundColor: 'rgba(255,118,46,0.08)', fill: true, tension: 0.3, borderWidth: 2 },
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
                    { label: @json(__('admin.chart_series_events')), data: charts.events_per_day, borderColor: brand, backgroundColor: 'rgba(113,56,245,0.08)', fill: true, tension: 0.3, borderWidth: 2 },
                    { label: @json(__('admin.chart_series_participants')), data: charts.participants_per_day, borderColor: accent, fill: false, tension: 0.3, borderWidth: 2 },
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
                    { label: @json(__('admin.chart_series_join_requests')), data: charts.join_requests_per_day, borderColor: brand, fill: false, tension: 0.3, borderWidth: 2 },
                    { label: @json(__('admin.chart_series_joins_accepted')), data: charts.joins_accepted_per_day, borderColor: '#159b76', fill: false, tension: 0.3, borderWidth: 2 },
                    { label: @json(__('admin.chart_series_event_messages')), data: charts.event_messages_per_day, borderColor: '#F59E0B', fill: false, tension: 0.3, borderWidth: 2 },
                    { label: @json(__('admin.chart_series_direct_messages')), data: charts.direct_messages_per_day, borderColor: '#EC4899', fill: false, tension: 0.3, borderWidth: 2 },
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
            options: doughnutOpts,
        });
    }

    if (el('admin-categories-chart')) {
        new Chart(el('admin-categories-chart'), {
            type: 'bar',
            data: {
                labels: charts.category_labels,
                datasets: [{ label: @json(__('admin.chart_series_events')), data: charts.category_values, backgroundColor: brand, borderRadius: 6 }],
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
            options: doughnutOpts,
        });
    }

    if (el('admin-geo-chart') && (charts.geo_event_labels || []).length) {
        new Chart(el('admin-geo-chart'), {
            type: 'bar',
            data: {
                labels: charts.geo_event_labels,
                datasets: [{ label: @json(__('admin.chart_series_events')), data: charts.geo_event_values, backgroundColor: accent, borderRadius: 6 }],
            },
            options: { ...lineOpts, indexAxis: 'y' },
        });
    }
});
</script>
@endpush
