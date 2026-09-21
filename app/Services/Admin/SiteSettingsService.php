<?php

namespace App\Services\Admin;

use App\Enums\PlatformRole;
use App\Exceptions\DomainException;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ChatMessageSanitizer;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    public const CACHE_KEY = 'site.display_name';

    public function __construct(private readonly AdminAuditLogger $audit)
    {
    }

    public function current(): SiteSetting
    {
        $settings = SiteSetting::query()->first();

        if ($settings === null) {
            $settings = SiteSetting::query()->create([
                'display_name' => (string) config('app.name', 'Community Platform'),
            ]);
        }

        return $settings;
    }

    public function displayName(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return $this->current()->display_name;
        });
    }

    public function updateDisplayName(User $actor, string $displayName): SiteSetting
    {
        if ($actor->platform_role !== PlatformRole::SuperAdmin) {
            throw new DomainException(__('admin.super_admin_only'));
        }

        $clean = ChatMessageSanitizer::clean($displayName);

        if ($clean === '' || mb_strlen($clean) > 80) {
            throw new DomainException(__('admin.invalid_display_name'));
        }

        $settings = $this->current();
        $previous = $settings->display_name;

        $settings->forceFill([
            'display_name' => $clean,
            'updated_by' => $actor->id,
        ])->save();

        Cache::forget(self::CACHE_KEY);

        $this->audit->log($actor, 'site_settings.updated', SiteSetting::class, $settings->id, [
            'previous' => $previous,
            'display_name' => $clean,
        ]);

        return $settings->refresh();
    }
}
