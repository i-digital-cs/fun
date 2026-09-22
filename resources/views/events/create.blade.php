@extends('layouts.app')

@section('title', __('events.create_event'))

@section('content')
    <div class="mx-auto max-w-2xl rounded-3xl border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.create_event') }}</h1>
        <form method="POST" action="{{ route('events.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            @include('events._form')
            <button class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-3 font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105 sm:w-auto">{{ __('events.create_event') }}</button>
        </form>
    </div>
@endsection
