@props([
    'suggestions' => [],
])

@php
    $items = collect($suggestions);
@endphp

@if ($items->isNotEmpty())
<section class="mt-12" id="people-you-may-like">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-[#19213D] sm:text-2xl">
                {{ __('friends.people_you_may_like') }}
            </h2>
            <p class="mt-1 text-sm text-[#64748B]">{{ __('friends.people_you_may_like_sub') }}</p>
        </div>
        <a href="{{ route('friends.index') }}" class="text-sm font-bold text-[#7138F5] hover:text-[#5825D6]">
            {{ __('friends.title') }}
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
            @endphp
            @if ($publicId)
                <article class="rounded-[1.25rem] border border-[#E8EAF2] bg-white/95 p-4 shadow-[0_16px_36px_-28px_rgba(25,33,61,0.4)]">
                    <a href="{{ route('users.public.show', $publicId) }}" class="flex items-center gap-3 no-underline">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" class="h-12 w-12 rounded-full object-cover" width="48" height="48">
                        @else
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#7138F5]/10 text-sm font-bold text-[#7138F5]">{{ $initials }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-[#19213D]">{{ $name }}</p>
                            @if ($cityLabel)
                                <p class="truncate text-xs text-[#64748B]">{{ $cityLabel }}</p>
                            @endif
                        </div>
                    </a>
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('users.public.show', $publicId) }}"
                           class="inline-flex flex-1 items-center justify-center rounded-xl border border-[#E8EAF2] px-3 py-2 text-xs font-bold text-[#19213D] hover:border-[#7138F5]/40">
                            {{ __('profile.view_profile') }}
                        </a>
                        @if ($canRequest)
                            <form method="POST" action="{{ route('friends.store') }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="public_id" value="{{ $publicId }}">
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#FF762E] to-[#FFB020] px-3 py-2 text-xs font-bold text-white shadow-sm">
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
