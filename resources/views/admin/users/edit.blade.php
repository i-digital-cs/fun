@extends('layouts.admin')

@section('title', __('admin.edit_user'))
@section('topbar_title', __('admin.edit_user'))

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-[25px] font-extrabold tracking-tight text-slate-900">{{ __('admin.edit_user') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 no-underline hover:border-indigo-300 hover:text-indigo-700">
        {{ __('admin.back_to_users') }}
    </a>
</div>

<div class="mx-auto max-w-xl rounded-[18px] border border-[#e7ebf3] bg-white p-6 shadow-[0_12px_35px_rgba(15,23,42,0.04)]">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('ui.name') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('ui.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
        </div>

        <div>
            <p class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.auth_method') }}</p>
            @php($authMethod = $user->authMethod())
            <p @class([
                'inline-flex rounded-full px-3 py-1.5 text-xs font-bold',
                'bg-sky-50 text-sky-700' => $authMethod === 'google',
                'bg-slate-100 text-slate-600' => $authMethod === 'password',
                'bg-violet-50 text-violet-700' => $authMethod === 'both',
            ])>{{ $user->authMethodLabel() }}</p>
            <p class="mt-1.5 text-xs text-slate-500">{{ __('admin.auth_method_help') }}</p>
        </div>

        <div>
            <label for="status" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.status') }}</label>
            <select id="status" name="status" required
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $user->status->value) === $status->value)>
                        {{ $status->value }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.password_optional') }}</p>
            <div class="space-y-3">
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('ui.password') }}</label>
                    <input id="password" type="password" name="password" autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('admin.password_confirmation') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
            {{ __('admin.save_user') }}
        </button>
    </form>
</div>
@endsection
