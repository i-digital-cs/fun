@extends('layouts.dashboard')

@section('title', __('safety.report_title'))

@section('content')
    <div class="mx-auto max-w-xl">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('safety.report_title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('safety.report') }}</p>

        <form method="POST" action="{{ route('reports.store') }}" class="mt-6 space-y-4 rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
            @csrf
            <div>
                <label for="reportable_type" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('safety.type') }}</label>
                <select id="reportable_type" name="reportable_type" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('reportable_type', $prefill['reportable_type']) === $type->value)>
                            {{ __('safety.types.'.$type->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="reportable_id" class="mb-1.5 block text-sm font-medium text-slate-700">ID</label>
                <input id="reportable_id" name="reportable_id" type="number" min="1" required value="{{ old('reportable_id', $prefill['reportable_id']) }}" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label for="reason" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('safety.reason') }}</label>
                <select id="reason" name="reason" required class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    @foreach ($reasons as $reason)
                        <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>
                            {{ __('safety.reasons.'.$reason->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('safety.description') }}</label>
                <textarea id="description" name="description" rows="4" maxlength="1000" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:brightness-105">{{ __('safety.submit_report') }}</button>
        </form>
    </div>
@endsection
