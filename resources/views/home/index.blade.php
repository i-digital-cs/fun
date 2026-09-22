@extends('layouts.app')

@section('title', $appName)

@section('content')
    <div class="space-y-2">
        <x-home.welcome :app-name="$appName" :user="$user" />
        <x-home.category-chips
            :categories="$categories"
            :published-total="$publishedTotal"
        />
        <x-home.happening
            :events="$events"
            :join-actions-by-id="$joinActionsById"
            :has-nearby-origin="$hasNearbyOrigin"
            :user="$user"
        />
    </div>
@endsection
