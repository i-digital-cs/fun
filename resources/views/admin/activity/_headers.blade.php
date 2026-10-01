@if ($metric === 'active_members')
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_member') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_email') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_status') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_last_activity') }}</th>
        <th class="px-4 py-3"></th>
    </tr>
@elseif ($metric === 'registrations')
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_member') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_email') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_status') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_registered_at') }}</th>
        <th class="px-4 py-3"></th>
    </tr>
@elseif ($metric === 'events_created')
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_event') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_creator') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_city') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_status') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_created_at') }}</th>
        <th class="px-4 py-3"></th>
    </tr>
@elseif (in_array($metric, ['join_requests', 'joins_accepted'], true))
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_member') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_event') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_status') }}</th>
        <th class="px-4 py-3">{{ $metric === 'joins_accepted' ? __('admin.col_accepted_at') : __('admin.col_requested_at') }}</th>
        <th class="px-4 py-3"></th>
    </tr>
@elseif ($metric === 'event_messages')
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_member') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_event') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_message') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_sent_at') }}</th>
        <th class="px-4 py-3"></th>
    </tr>
@elseif ($metric === 'direct_messages')
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_from') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_to') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_message') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_sent_at') }}</th>
    </tr>
@elseif (in_array($metric, ['friend_requests', 'friend_accepts'], true))
    <tr>
        <th class="px-4 py-3">{{ __('admin.col_from') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_to') }}</th>
        <th class="px-4 py-3">{{ __('admin.col_status') }}</th>
        <th class="px-4 py-3">{{ $metric === 'friend_accepts' ? __('admin.col_accepted_at') : __('admin.col_requested_at') }}</th>
        <th class="px-4 py-3"></th>
    </tr>
@endif
