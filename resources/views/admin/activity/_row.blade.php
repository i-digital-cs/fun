@php
    $fmt = fn ($dt) => $dt ? \Illuminate\Support\Carbon::parse($dt)->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—';
@endphp

@if ($metric === 'active_members')
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3 font-semibold text-slate-900">{{ $row->name }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->email }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->status->value }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt(isset($row->session_last_activity) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $row->session_last_activity) : null) }}</td>
        <td class="px-4 py-3 text-end">
            <a href="{{ route('admin.users.edit', $row) }}" class="text-xs font-bold text-indigo-600 no-underline hover:underline">{{ __('admin.view') }}</a>
        </td>
    </tr>
@elseif ($metric === 'registrations')
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3 font-semibold text-slate-900">{{ $row->name }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->email }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->status->value }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt($row->created_at) }}</td>
        <td class="px-4 py-3 text-end">
            <a href="{{ route('admin.users.edit', $row) }}" class="text-xs font-bold text-indigo-600 no-underline hover:underline">{{ __('admin.view') }}</a>
        </td>
    </tr>
@elseif ($metric === 'events_created')
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3 font-semibold text-slate-900">{{ $row->title }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->creator?->name ?? '—' }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->city?->localizedName() ?? '—' }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->status->value }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt($row->created_at) }}</td>
        <td class="px-4 py-3 text-end">
            <a href="{{ route('admin.events.edit', $row) }}" class="text-xs font-bold text-indigo-600 no-underline hover:underline">{{ __('admin.view') }}</a>
        </td>
    </tr>
@elseif (in_array($metric, ['join_requests', 'joins_accepted'], true))
    @php
        $when = $metric === 'joins_accepted'
            ? ($row->reviewed_at ?? $row->updated_at)
            : $row->created_at;
    @endphp
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3">
            <p class="font-semibold text-slate-900">{{ $row->user?->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $row->user?->email }}</p>
        </td>
        <td class="px-4 py-3 text-slate-700">{{ $row->event?->title ?? '—' }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $row->status->value }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt($when) }}</td>
        <td class="px-4 py-3 text-end">
            @if ($row->event)
                <a href="{{ route('admin.events.edit', $row->event) }}" class="text-xs font-bold text-indigo-600 no-underline hover:underline">{{ __('admin.view') }}</a>
            @endif
        </td>
    </tr>
@elseif ($metric === 'event_messages')
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3 font-semibold text-slate-900">{{ $row->user?->name ?? '—' }}</td>
        <td class="px-4 py-3 text-slate-700">{{ $row->event?->title ?? '—' }}</td>
        <td class="max-w-md px-4 py-3 text-slate-600">
            <span class="line-clamp-2">{{ $row->body }}</span>
        </td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt($row->created_at) }}</td>
        <td class="px-4 py-3 text-end">
            @if ($row->event)
                <a href="{{ route('admin.chat.index', $row->event) }}" class="text-xs font-bold text-indigo-600 no-underline hover:underline">{{ __('admin.moderate_chat') }}</a>
            @endif
        </td>
    </tr>
@elseif ($metric === 'direct_messages')
    @php
        $senderId = (int) $row->user_id;
        $peer = null;
        if ($row->conversation) {
            $peer = $senderId === (int) $row->conversation->user_low_id
                ? $row->conversation->userHigh
                : $row->conversation->userLow;
        }
    @endphp
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3">
            <p class="font-semibold text-slate-900">{{ $row->user?->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $row->user?->email }}</p>
        </td>
        <td class="px-4 py-3">
            <p class="font-semibold text-slate-900">{{ $peer?->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $peer?->email }}</p>
        </td>
        <td class="max-w-md px-4 py-3 text-slate-600">
            <span class="line-clamp-2">{{ $row->body }}</span>
        </td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt($row->created_at) }}</td>
    </tr>
@elseif (in_array($metric, ['friend_requests', 'friend_accepts'], true))
    <tr class="hover:bg-slate-50/80">
        <td class="px-4 py-3">
            <p class="font-semibold text-slate-900">{{ $row->requester?->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $row->requester?->email }}</p>
        </td>
        <td class="px-4 py-3">
            <p class="font-semibold text-slate-900">{{ $row->addressee?->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $row->addressee?->email }}</p>
        </td>
        <td class="px-4 py-3 text-slate-600">{{ $row->status->value }}</td>
        <td class="px-4 py-3 text-slate-600">{{ $fmt($metric === 'friend_accepts' ? $row->updated_at : $row->created_at) }}</td>
        <td class="px-4 py-3 text-end">
            @if ($row->requester)
                <a href="{{ route('admin.users.edit', $row->requester) }}" class="text-xs font-bold text-indigo-600 no-underline hover:underline">{{ __('admin.view') }}</a>
            @endif
        </td>
    </tr>
@endif
