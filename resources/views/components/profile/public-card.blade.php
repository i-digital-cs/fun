@props([
    'profile', // array from PublicProfileService::toPublicArray
    'showActions' => true,
])

@php
    $name = $profile['name'] ?? '';
    $role = $profile['role_label'] ?? '';
    $photo = $profile['profile_image_path'] ?? null;
    $initials = $profile['initials'] ?? user_initials($name);
    $stats = $profile['stats'] ?? [];
    $publicId = $profile['public_id'] ?? '';
    $cityName = $profile['city']['name'] ?? null;
@endphp

{{-- Uiverse.io by Yaya12085 — brand indigo profile card --}}
<div {{ $attributes->class(['profile-card']) }}>
    <div class="profile-card__infos">
        <div class="profile-card__image" @if ($photo) style="background-image: url('{{ $photo }}')" @endif>
            @unless ($photo)
                <span class="profile-card__initials">{{ $initials }}</span>
            @endunless
        </div>
        <div class="profile-card__info">
            <div>
                <p class="profile-card__name">{{ $name }}</p>
                <p class="profile-card__function">
                    {{ $role }}
                    @if ($cityName)
                        · {{ $cityName }}
                    @endif
                </p>
                @if ($publicId)
                    <p class="profile-card__id">{{ $publicId }}</p>
                @endif
            </div>
            <div class="profile-card__stats">
                <p class="profile-card__stat">
                    {{ __('profile.stat_hosted') }}
                    <span class="profile-card__stat-value">{{ $stats['hosted_events'] ?? 0 }}</span>
                </p>
                <p class="profile-card__stat">
                    {{ __('profile.stat_joined') }}
                    <span class="profile-card__stat-value">{{ $stats['joined_events'] ?? 0 }}</span>
                </p>
                <p class="profile-card__stat">
                    {{ __('profile.stat_rating') }}
                    <span class="profile-card__stat-value">
                        @if (($stats['rating_count'] ?? 0) > 0)
                            {{ $stats['rating_average'] }}★
                        @else
                            —
                        @endif
                    </span>
                </p>
            </div>
        </div>
    </div>

    @if ($showActions)
        <div class="profile-card__actions">
            {{ $slot }}
        </div>
    @endif
</div>
