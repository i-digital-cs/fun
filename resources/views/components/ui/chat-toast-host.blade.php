{{-- Uiverse.io by Manish-Tamang — message toast host --}}
<div id="chat-toast-host"
     class="chat-toast-host"
     data-user-id="{{ auth()->id() }}"
     data-label-new="{{ __('chat.toast_new') }}"
     data-label-just-now="{{ __('chat.toast_just_now') }}"
     aria-live="polite"></div>
