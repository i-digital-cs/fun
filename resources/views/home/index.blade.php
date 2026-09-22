@extends('layouts.app')

@section('title', $appName)

@section('content')
    <div class="space-y-2">
        <x-home.welcome :app-name="$appName" :user="$user" :when="$when" />
        <x-home.category-chips
            :categories="$categories"
            :published-total="$publishedTotal"
        />
        @auth
            @if ($user?->canParticipate())
                <x-home.participations
                    :memberships="$memberships"
                    :pending-requests="$pendingRequests"
                />
            @endif
        @endauth
        <x-home.happening
            :events="$events"
            :join-actions-by-id="$joinActionsById"
            :has-nearby-origin="$hasNearbyOrigin"
            :user="$user"
        />
        <x-home.map-create
            :user="$user"
            :events-count="$events->count()"
            :published-total="$publishedTotal"
        />
        <x-home.trust :user="$user" />
    </div>
@endsection
