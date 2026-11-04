@php
    $context = $mailContext ?? 'verify';
    if (($purpose ?? '') === 'password_reset') {
        $context = 'password_reset';
    }
    $headingKey = match ($context) {
        'password_reset' => 'auth.mail_reset_heading',
        'login' => 'auth.mail_login_heading',
        'email_change' => 'auth.mail_email_change_heading',
        default => 'auth.mail_verify_heading',
    };
    $bodyKey = match ($context) {
        'password_reset' => 'auth.mail_reset_body',
        'login' => 'auth.mail_login_body',
        'email_change' => 'auth.mail_email_change_body',
        default => 'auth.mail_verify_body',
    };
@endphp
{{ $siteName }}

{{ __($headingKey, ['app' => $siteName]) }}

{{ $greeting }}

{{ __($bodyKey) }}

{{ $code }}

{{ __('auth.mail_expires', ['minutes' => $expiresMinutes]) }}

{{ __('auth.mail_ignore') }}

{{ __('auth.mail_thanks') }},
{{ $siteName }}
