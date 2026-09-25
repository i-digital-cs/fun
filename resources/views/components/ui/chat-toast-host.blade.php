{{-- Uiverse.io by Manish-Tamang — message / join toast host --}}
<div id="chat-toast-host"
     class="chat-toast-host"
     data-user-id="{{ auth()->id() }}"
     data-label-new="{{ __('chat.toast_new') }}"
     data-label-join-accepted="{{ __('events.notify_join_accepted_title') }}"
     data-label-just-now="{{ __('chat.toast_just_now') }}"
     aria-live="polite"></div>
