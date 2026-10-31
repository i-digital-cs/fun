@extends('layouts.dashboard')

@section('title', __('events.create_event'))

@section('content')
    <div class="mx-auto max-w-2xl rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.create_event') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('events.create_intent_hint') }}</p>
        <form method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @include('events._form')
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="submit"
                    name="publish_intent"
                    value="1"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105 sm:w-auto"
                >{{ __('events.publish_event') }}</button>
                <button
                    type="submit"
                    name="publish_intent"
                    value="0"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-3 font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700 sm:w-auto"
                >{{ __('events.save_draft') }}</button>
            </div>
        </form>
    </div>
@endsection
