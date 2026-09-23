@props([
    'event',
    'joinAction' => null,
    'favorited' => false,
])

{{-- Same 3D glass + cover + members language as discover. --}}
<x-events.card :event="$event" :join-action="$joinAction" :favorited="$favorited" {{ $attributes }} />
