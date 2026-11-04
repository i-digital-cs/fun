@props([
    /** @var 'verify'|'reset' */
    'context' => 'verify',
])

@php
    $title = $context === 'reset'
        ? __('ui.email_tip_reset_title')
        : __('ui.email_tip_verify_title');
    $body = $context === 'reset'
        ? __('ui.email_tip_reset_body')
        : __('ui.email_tip_verify_body');
@endphp

<div
    {{ $attributes->class([
        'mt-5 rounded-2xl border border-[#E8E0FF] bg-[#F1EBFF]/90 px-3.5 py-3 text-start shadow-sm shadow-[#7138F5]/5',
    ]) }}
    data-email-delivery-tip
    role="note"
>
    <div class="flex items-start gap-3">
        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-[#7138F5] shadow-sm shadow-[#7138F5]/10" aria-hidden="true">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />
                <path d="m5 7 7 5 7-5" />
            </svg>
        </span>
        <div class="min-w-0">
            <p class="text-sm font-bold text-[#3B1577]">{{ $title }}</p>
            <p class="mt-1 text-[0.8125rem] leading-relaxed text-slate-600">{{ $body }}</p>
        </div>
    </div>
</div>
