@extends('layouts.admin')

@section('title', __('marketing.budget_heading'))

@push('head')
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=tajawal:400,500,600,700&display=swap">
    <style>
        html[lang^="ar"] .marketing-shell { font-family: Tajawal, "IBM Plex Sans Arabic", sans-serif; }
    </style>
@endpush

@section('content')
<div class="marketing-shell space-y-6">
    @include('admin.marketing._nav')

    <header>
        <h1 class="text-2xl font-extrabold tracking-tight text-[#19213D]">{{ __('marketing.budget_heading') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ __('marketing.budget_help') }}</p>
    </header>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="note">
        {{ __('marketing.budget_disclaimer') }}
    </div>

    <section class="rounded-2xl border border-[#E8EAF2] bg-white p-6 shadow-sm">
        <h2 class="text-base font-bold text-[#19213D]">{{ __('marketing.budget_create') }}</h2>
        <form method="POST" action="{{ route('admin.marketing.budget-policies.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label for="scope_type" class="mb-1 block text-sm font-medium">{{ __('marketing.field_scope_type') }}</label>
                <select id="scope_type" name="scope_type" required class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
                    @foreach ($scopeTypes as $scope)
                        <option value="{{ $scope->value }}" @selected(old('scope_type', 'global') === $scope->value)>
                            {{ __('marketing.scope_'.$scope->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="scope_id" class="mb-1 block text-sm font-medium">{{ __('marketing.field_scope_id') }}</label>
                <input id="scope_id" name="scope_id" type="number" min="1" value="{{ old('scope_id') }}" class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
            </div>
            <div>
                <label for="max_daily_spend" class="mb-1 block text-sm font-medium">{{ __('marketing.field_max_daily') }}</label>
                <input id="max_daily_spend" name="max_daily_spend" type="number" min="0" step="0.01" value="{{ old('max_daily_spend') }}" class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
            </div>
            <div>
                <label for="max_monthly_spend" class="mb-1 block text-sm font-medium">{{ __('marketing.field_max_monthly') }}</label>
                <input id="max_monthly_spend" name="max_monthly_spend" type="number" min="0" step="0.01" value="{{ old('max_monthly_spend') }}" class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
            </div>
            <div>
                <label for="max_budget_increase_pct" class="mb-1 block text-sm font-medium">{{ __('marketing.field_increase_pct') }}</label>
                <input id="max_budget_increase_pct" name="max_budget_increase_pct" type="number" min="0" step="0.01" value="{{ old('max_budget_increase_pct', $defaults['max_budget_increase_pct'] ?? 20) }}" class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
            </div>
            <div>
                <label for="max_budget_decrease_pct" class="mb-1 block text-sm font-medium">{{ __('marketing.field_decrease_pct') }}</label>
                <input id="max_budget_decrease_pct" name="max_budget_decrease_pct" type="number" min="0" step="0.01" value="{{ old('max_budget_decrease_pct', $defaults['max_budget_decrease_pct'] ?? 50) }}" class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
            </div>
            <div>
                <label for="currency" class="mb-1 block text-sm font-medium">{{ __('marketing.field_currency') }}</label>
                <input id="currency" name="currency" type="text" maxlength="3" required value="{{ old('currency', $defaults['currency'] ?? 'USD') }}" class="w-full rounded-xl border border-[#E8EAF2] bg-[#F8F9FE] px-3 py-2 text-sm uppercase focus:border-[#7138F5] focus:outline-none focus:ring-2 focus:ring-[#7138F5]/30">
            </div>
            <div class="flex flex-wrap items-end gap-4 sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="allow_status_pause" value="1" class="rounded border-slate-300 text-[#7138F5] focus:ring-[#7138F5]" @checked(old('allow_status_pause', true))>
                    {{ __('marketing.field_allow_pause') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="allow_status_resume" value="1" class="rounded border-slate-300 text-[#7138F5] focus:ring-[#7138F5]" @checked(old('allow_status_resume', true))>
                    {{ __('marketing.field_allow_resume') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="allow_create" value="1" class="rounded border-slate-300 text-[#7138F5] focus:ring-[#7138F5]" @checked(old('allow_create', false))>
                    {{ __('marketing.field_allow_create') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="active" value="1" class="rounded border-slate-300 text-[#7138F5] focus:ring-[#7138F5]" @checked(old('active', true))>
                    {{ __('marketing.field_active') }}
                </label>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-xl bg-[#7138F5] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-[#7138F5]/25 hover:bg-[#5825D6]">
                    {{ __('marketing.budget_create') }}
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-x-auto rounded-2xl border border-[#E8EAF2] bg-white shadow-sm">
        @if ($policies->isEmpty())
            <p class="p-6 text-sm text-slate-600">{{ __('marketing.budget_empty') }}</p>
        @else
            <table class="min-w-full text-start text-sm">
                <thead class="border-b border-[#E8EAF2] bg-[#F8F9FE] text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-bold">ID</th>
                        <th class="px-4 py-3 font-bold">{{ __('marketing.field_scope_type') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('marketing.field_max_daily') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('marketing.field_max_monthly') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('marketing.field_currency') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('marketing.field_status') }}</th>
                        <th class="px-4 py-3 font-bold">{{ __('marketing.field_allow_create') }}</th>
                        <th class="px-4 py-3 font-bold"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($policies as $policy)
                        <tr class="border-b border-[#E8EAF2] last:border-0">
                            <td class="px-4 py-3 font-semibold text-[#19213D]">{{ $policy->id }}</td>
                            <td class="px-4 py-3">{{ __('marketing.scope_'.$policy->scope_type->value) }}@if($policy->scope_id) #{{ $policy->scope_id }}@endif</td>
                            <td class="px-4 py-3">{{ $policy->max_daily_spend ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $policy->max_monthly_spend ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $policy->currency }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                    'bg-emerald-50 text-emerald-700' => $policy->active,
                                    'bg-slate-100 text-slate-600' => ! $policy->active,
                                ])>
                                    {{ $policy->active ? __('marketing.active') : __('marketing.inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $policy->allow_create ? __('marketing.yes') : __('marketing.no') }}</td>
                            <td class="px-4 py-3 text-end">
                                @if ($policy->active)
                                    <form method="POST" action="{{ route('admin.marketing.budget-policies.disable', $policy) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ __('marketing.budget_disable') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.marketing.budget-policies.enable', $policy) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#7138F5] hover:bg-[#F1EBFF]">{{ __('marketing.budget_enable') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
