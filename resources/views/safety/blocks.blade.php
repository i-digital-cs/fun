@extends('layouts.dashboard')

@section('title', __('safety.blocked_users'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('safety.blocked_users') }}</h1>
    </div>

    @if ($blocks->isEmpty())
        <div class="rounded-3xl border border-slate-200/70 bg-white p-8 text-center shadow-sm">
            <p class="text-slate-500">{{ __('safety.no_blocks') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($blocks as $block)
                <div class="flex items-center justify-between gap-3 rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $block->blocked?->name }}</p>
                        <p class="text-sm text-slate-500">{{ $block->blocked?->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('users.unblock', $block->blocked_id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('safety.unblock') }}</button>
                    </form>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $blocks->links() }}</div>
    @endif
@endsection
