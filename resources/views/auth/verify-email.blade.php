@extends('layouts.app')

@section('title', __('ui.verify_email'))

@section('content')
    <x-auth.access-shell
        :action="route('verification.verify')"
        mode="otp"
        digit-source="code"
        :title="__('ui.access_enter_title')"
        :subtitle="__('ui.verify_email_sub', ['email' => $email])"
        :enter-title="__('ui.access_enter_title')"
        :enter-sub="__('ui.access_enter_sub')"
        :granted-title="__('ui.access_granted_title')"
        :granted-sub="__('ui.access_granted_sub')"
        :resend-url="route('verification.send')"
    />
@endsection

@push('scripts')
    @vite(['resources/js/auth-access.js'])
@endpush
