@php
    $locale = $locale ?? app()->getLocale();
    $dir = ($locale === 'ar') ? 'rtl' : 'ltr';
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

    $heading = __($headingKey, ['app' => $siteName]);
    $body = __($bodyKey);
    $title = $heading;
    $preheader = __('auth.mail_preheader', ['code_hint' => __('auth.mail_preheader_code')]);
@endphp

@component('emails.layouts.transactional', [
    'locale' => $locale,
    'dir' => $dir,
    'siteName' => $siteName,
    'logoUrl' => $logoUrl,
    'title' => $title,
    'preheader' => $preheader,
])
    <h1 style="margin:0 0 12px 0;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:700;line-height:1.3;color:#0F172A;">
        {{ $heading }}
    </h1>

    <p style="margin:0 0 12px 0;">
        {{ $greeting }}
    </p>

    <p style="margin:0 0 4px 0;">
        {{ $body }}
    </p>

    @include('emails.partials.otp-code', ['code' => $code])

    <p style="margin:0 0 12px 0;font-size:14px;color:#475569;">
        {{ __('auth.mail_expires', ['minutes' => $expiresMinutes]) }}
    </p>

    <p style="margin:0 0 16px 0;font-size:13px;line-height:1.5;color:#64748B;">
        {{ __('auth.mail_ignore') }}
    </p>

    <p style="margin:0;color:#334155;">
        {{ __('auth.mail_thanks') }},<br />
        <strong style="color:#7138F5;">{{ $siteName }}</strong>
    </p>
@endcomponent
