{{-- Uiverse.io by Manish-Tamang — message / join toast host --}}
<div id="chat-toast-host"
     class="chat-toast-host"
     data-user-id="{{ auth()->id() }}"
     data-label-new="{{ __('chat.toast_new') }}"
     data-label-join-accepted="{{ __('events.notify_join_accepted_title') }}"
     data-label-join-requested="{{ __('events.notify_join_request_title') }}"
     data-label-reaction="{{ __('chat.toast_reaction') }}"
     data-label-friend-request="{{ __('friends.notify_request_title') }}"
     data-label-friend-accepted="{{ __('friends.notify_accepted_title') }}"
     data-label-dm="{{ __('friends.dm_notify_title') }}"
     data-label-dm-body="{{ __('friends.dm_notify_body_generic') }}"
     data-label-just-now="{{ __('chat.toast_just_now') }}"
     aria-live="polite"></div>
