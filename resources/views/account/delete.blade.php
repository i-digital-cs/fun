@extends('layouts.dashboard')

@section('title', __('account.delete_title'))

@section('content')
    @php
        $user = $user ?? auth()->user();
        $hasPassword = filled($user->getAuthPassword());
        $pending = $user->deletion_requested_at;
    @endphp

    <div class="mx-auto max-w-xl space-y-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('account.delete_title') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('account.delete_intro', ['days' => $graceDays]) }}</p>
        </div>

        @if (session('status'))
            <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif

        @if ($pending)
            <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <p class="text-sm font-medium text-amber-900">
                    {{ __('account.pending_banner', ['date' => $pending->copy()->addDays($graceDays)->timezone(config('app.timezone'))->format('Y-m-d')]) }}
                </p>
                <form method="POST" action="{{ route('settings.account.delete.cancel') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-indigo-500">
                        {{ __('account.delete_cancel') }}
                    </button>
                </form>
            </section>
        @else
            <section class="rounded-3xl border border-rose-200/80 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('settings.account.delete.destroy') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    @if ($hasPassword)
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('account.password') }}</label>
                            <input id="password" type="password" name="password" required autocomplete="current-password"
                                   class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm focus:border-rose-500 focus:outline-none focus:ring-4 focus:ring-rose-100">
                            @error('password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <input type="hidden" name="provider_reauth" value="confirmed">
                        <p class="text-sm text-slate-600">{{ __('account.deletion_reauth_required') }}</p>
                        @error('password')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    @endif

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-rose-500"
                            onclick="return confirm(@json(__('account.delete_confirm')).concat('?'))">
                        {{ __('account.delete_confirm') }}
                    </button>
                </form>
            </section>
        @endif

        <p class="text-sm text-slate-500">
            <a href="{{ route('legal.account-deletion') }}" class="font-semibold text-indigo-600 hover:underline">{{ __('account_deletion.title') }}</a>
            ·
            <a href="{{ route('legal.support') }}" class="font-semibold text-indigo-600 hover:underline">{{ __('support.footer_link') }}</a>
        </p>
    </div>
@endsection
