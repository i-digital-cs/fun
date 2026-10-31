@extends('layouts.app')

@section('title', __('ui.verify_reset_code'))

@section('content')
    <x-auth.access-shell
        :action="route('password.verify.store')"
        mode="otp"
        digit-source="code"
        :title="__('ui.access_enter_title')"
        :subtitle="__('ui.verify_email_sub', ['email' => $email])"
        :resend-url="route('password.resend')"
        :granted-title="__('ui.access_granted_title')"
        :granted-sub="__('auth.reset_code_accepted')"
        :retry-after-seconds="(int) ($otp['retry_after_seconds'] ?? 0)"
    />
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
@endpush
