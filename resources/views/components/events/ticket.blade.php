@props([
    'ticket',
    'filterId' => null,
])

@php
    $filterId = $filterId ?: 'event-ticket-bump-'.\Illuminate\Support\Str::lower($ticket['code']);
    $roleLabel = match ($ticket['role']) {
        'owner' => __('events.ticket_role_owner'),
        'admin' => __('events.ticket_role_admin'),
        default => __('events.ticket_role_member'),
    };
@endphp

<div {{ $attributes->class(['event-ticket']) }} role="img" aria-label="{{ __('events.ticket_aria', ['title' => $ticket['title']]) }}">
    <div class="event-ticket__notes" aria-hidden="true">♪♪♪♪♪</div>
    <div class="event-ticket__notes" aria-hidden="true">♪♪♪♪</div>
    <div class="event-ticket__notes" aria-hidden="true">♪♪♪♪♪</div>

    <div class="event-ticket__header">
        {{ __('events.ticket_word') }}
        <div class="event-ticket__symbol" aria-hidden="true">✁</div>
    </div>

    <div class="event-ticket__body">
        <em>{{ $ticket['pass_label'] }}</em><br>
        <strong class="event-ticket__title">{{ $ticket['title'] }}</strong><br>
        {{ $ticket['date_label'] }}<br>
        {{ $ticket['venue_line'] }}
    </div>

    <div class="event-ticket__footer">
        <div class="event-ticket__number">
            {{ $roleLabel }}
            <span class="event-ticket__bold">{{ $ticket['code'] }}</span>
        </div>
        <div class="event-ticket__barcode" aria-hidden="true"></div>
    </div>

    <div class="event-ticket__bg event-ticket__bg--holographic"></div>

    <svg class="event-ticket__filter" aria-hidden="true" width="0" height="0">
        <filter id="{{ $filterId }}">
            <feTurbulence
                result="noise"
                numOctaves="3"
                baseFrequency="0.7"
                type="fractalNoise"
            ></feTurbulence>
            <feSpecularLighting
                in="noise"
                result="specular"
                lighting-color="#fffffc"
                specularExponent="25"
                specularConstant="0.8"
                surfaceScale="0.15"
            >
                <fePointLight z="210" y="100" x="100"></fePointLight>
            </feSpecularLighting>
            <feComposite
                result="noise2"
                operator="in"
                in="specular"
                in2="SourceGraphic"
            ></feComposite>
            <feBlend mode="screen" in2="noise2" in="SourceGraphic"></feBlend>
        </filter>
    </svg>
</div>

<style>
    .event-ticket {
        --event-ticket-filter: url(#{{ $filterId }});
    }
</style>
