@extends('layouts.home')

@section('title', $appName)

@section('content')
    <x-community.hero
        :user="$user"
        :city-name="$cityName"
        :when="$when"
        :categories="$categories"
        :published-total="$publishedTotal"
        :highlight="$highlight"
        :stats="$stats"
    />

    @auth
        @if ($user?->canParticipate())
            <x-community.participations
                :memberships="$memberships"
                :pending-requests="$pendingRequests"
            />
        @endif
    @endauth

    <x-community.discover
        :events="$events"
        :join-actions-by-id="$joinActionsById"
        :user="$user"
        :city-name="$cityName"
        :nationwide-fallback="$nationwideFallback"
    />

    <x-community.trust />
@endsection
