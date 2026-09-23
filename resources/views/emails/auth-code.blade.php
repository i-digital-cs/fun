<x-mail::message>
@if ($logoUrl)
<div style="text-align:center;margin-bottom:24px;">
<img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height:56px;max-width:180px;">
</div>
@endif

# {{ $purpose === 'password_reset' ? __('auth.mail_reset_heading', ['app' => $siteName]) : __('auth.mail_verify_heading', ['app' => $siteName]) }}

{{ __('auth.mail_greeting', ['name' => $userName]) }}

{{ $purpose === 'password_reset' ? __('auth.mail_reset_body') : __('auth.mail_verify_body') }}

<div style="text-align:center;margin:28px 0;">
<span style="display:inline-block;letter-spacing:0.35em;font-size:28px;font-weight:700;color:#312e81;background:#eef2ff;border-radius:12px;padding:12px 20px;">{{ $code }}</span>
</div>

{{ __('auth.mail_expires', ['minutes' => $expiresMinutes]) }}

{{ __('auth.mail_ignore') }}

{{ __('auth.mail_thanks') }},<br>
{{ $siteName }}
</x-mail::message>
