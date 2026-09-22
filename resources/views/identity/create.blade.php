@extends('layouts.app')

@section('title', __('identity.title'))

@section('content')
    <div class="mx-auto max-w-xl rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ __('identity.title') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('identity.help') }}</p>

        <form method="POST" action="{{ route('identity.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf
            @foreach ([
                'id_front' => __('identity.id_front'),
                'id_back' => __('identity.id_back'),
                'selfie' => __('identity.selfie'),
            ] as $name => $label)
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="{{ $name }}">{{ $label }}</label>
                    <input id="{{ $name }}" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" required
                           class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-4 py-8 text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700">
                </div>
            @endforeach

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                {{ __('identity.submit') }}
            </button>
        </form>
    </div>
@endsection
