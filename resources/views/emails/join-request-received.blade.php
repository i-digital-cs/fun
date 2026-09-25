<x-mail::message>
@if ($logoUrl)
<div style="text-align:center;margin-bottom:24px;">
<img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height:56px;max-width:180px;">
</div>
@endif

# {{ __('events.mail_join_request_heading') }}

{{ __('events.mail_greeting', ['name' => $organizerName]) }}

{{ __('events.mail_join_request_body', ['requester' => $requesterName, 'event' => $eventTitle]) }}

@if (filled($message))
> {{ $message }}
@endif

<x-mail::button :url="$reviewUrl">
{{ __('events.mail_join_request_cta') }}
</x-mail::button>

{{ __('events.mail_join_request_event_link') }}: [{{ $eventTitle }}]({{ $eventUrl }})

{{ __('events.mail_thanks') }},<br>
{{ $siteName }}
</x-mail::message>
