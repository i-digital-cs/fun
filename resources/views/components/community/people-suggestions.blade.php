@props([
    'suggestions' => [],
])

@php
    $items = collect($suggestions);
@endphp

@if ($items->isNotEmpty())
<section class="mt-2" id="people-you-may-like">
    <div class="mb-3.5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-[1.15rem] font-extrabold tracking-tight text-[#19213D]">
                {{ __('friends.people_you_may_like') }}
            </h2>
            <p class="mt-0.5 text-[12px] text-slate-500">{{ __('friends.people_you_may_like_sub') }}</p>
        </div>
        <a href="{{ route('friends.index') }}" class="text-xs font-bold text-[#7138F5] no-underline hover:text-[#5825D6]">
            {{ __('friends.title') }} →
        </a>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($items as $row)
            @php
                $person = $row['user'] ?? [];
                $friendship = $row['friendship'] ?? [];
                $publicId = $person['public_id'] ?? null;
                $name = $person['name'] ?? '';
                $initials = $person['initials'] ?? '?';
                $photo = $person['profile_image_path'] ?? null;
                $cityLabel = $person['city']['name'] ?? null;
                $canRequest = ($friendship['can_request'] ?? false) === true;
                $pendingOut = ($friendship['status'] ?? '') === 'pending'
                    && ($friendship['direction'] ?? '') === 'outgoing';
            @endphp
            @if ($publicId)
                <article class="flex flex-col items-center rounded-[1.15rem] border border-[#E8EAF2] bg-white p-4 text-center shadow-[0_12px_28px_-22px_rgba(25,33,61,0.4)]">
                    <a href="{{ route('users.public.show', $publicId) }}" class="no-underline">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" class="mx-auto h-14 w-14 rounded-full object-cover" width="56" height="56">
                        @else
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#7138F5]/12 text-sm font-extrabold text-[#7138F5]">{{ $initials }}</span>
                        @endif
                        <p class="mt-2.5 line-clamp-2 text-sm font-extrabold text-[#19213D]">{{ $name }}</p>
                    </a>
                    @if ($cityLabel)
                        <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $cityLabel }}</p>
                    @endif
                    <p class="mt-1 text-[10px] text-slate-400">{{ __('friends.no_shared_interests_yet') }}</p>
                    <div class="mt-3 flex w-full gap-2">
                        <a href="{{ route('users.public.show', $publicId) }}"
                           class="inline-flex flex-1 items-center justify-center rounded-xl border border-[#E8EAF2] px-2 py-2 text-[11px] font-bold text-[#19213D] no-underline hover:border-[#7138F5]/35">
                            {{ __('profile.view_profile') }}
                        </a>
                        @if ($pendingOut)
                            <span class="inline-flex flex-1 items-center justify-center rounded-xl bg-slate-100 px-2 py-2 text-[11px] font-bold text-slate-500">
                                {{ __('friends.request_sent') }}
                            </span>
                        @elseif ($canRequest)
                            <form method="POST" action="{{ route('friends.store') }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="public_id" value="{{ $publicId }}">
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-[#7138F5] px-2 py-2 text-[11px] font-extrabold text-white hover:bg-[#5825D6]">
                                    {{ __('friends.add_friend') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endif
        @endforeach
    </div>
</section>
@endif
