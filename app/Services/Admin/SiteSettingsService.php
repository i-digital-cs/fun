<?php

namespace App\Services\Admin;

use App\Enums\PlatformRole;
use App\Exceptions\DomainException;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ChatMessageSanitizer;
use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteSettingsService
{
    public const CACHE_KEY = 'site.display_name';

    public const LOGO_CACHE_KEY = 'site.logo_url';

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

    public function logoUrl(): ?string
    {
        return Cache::remember(self::LOGO_CACHE_KEY, now()->addMinutes(10), function () {
            $settings = $this->current();

            if (! filled($settings->logo_path) || ! filled($settings->logo_disk)) {
                return null;
            }

            try {
                return Storage::disk($settings->logo_disk)->url($settings->logo_path);
            } catch (\Throwable) {
                return null;
            }
        });
    }

    /**
     * @param  array{display_name: string, logo?: UploadedFile|null, remove_logo?: bool}  $data
     */
    public function update(User $actor, array $data): SiteSetting
    {
        if ($actor->platform_role !== PlatformRole::SuperAdmin) {
            throw new DomainException(__('admin.super_admin_only'));
        }

        $clean = ChatMessageSanitizer::clean($data['display_name']);

        if ($clean === '' || mb_strlen($clean) > 80) {
            throw new DomainException(__('admin.invalid_display_name'));
        }

        $settings = $this->current();
        $previous = [
            'display_name' => $settings->display_name,
            'logo_path' => $settings->logo_path,
        ];

        $settings->forceFill([
            'display_name' => $clean,
            'updated_by' => $actor->id,
        ]);

        if (! empty($data['remove_logo'])) {
            $this->deleteLogoFiles($settings);
            $settings->logo_path = null;
            $settings->logo_disk = null;
        }

        if (($data['logo'] ?? null) instanceof UploadedFile) {
            $stored = $this->storeLogo($data['logo']);
            $this->deleteLogoFiles($settings);
            $settings->logo_path = $stored['path'];
            $settings->logo_disk = $stored['disk'];
        }

        $settings->save();

        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LOGO_CACHE_KEY);

        $this->audit->log($actor, 'site_settings.updated', SiteSetting::class, $settings->id, [
            'previous' => $previous,
            'display_name' => $clean,
            'logo_updated' => ($data['logo'] ?? null) instanceof UploadedFile || ! empty($data['remove_logo']),
        ]);

        return $settings->refresh();
    }

    /**
     * @deprecated Use update()
     */
    public function updateDisplayName(User $actor, string $displayName): SiteSetting
    {
        return $this->update($actor, ['display_name' => $displayName]);
    }

    /**
     * @return array{disk: string, path: string}
     */
    private function storeLogo(UploadedFile $file): array
    {
        $disk = MediaDisk::public();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'png';
        $filename = sprintf('logo_%s.%s', Str::uuid()->toString(), $safeExtension);

        $path = $file->storeAs('branding', $filename, [
            'disk' => $disk,
            'visibility' => 'public',
        ]);

        if ($path === false) {
            throw new DomainException(__('admin.logo_upload_failed'));
        }

        return [
            'disk' => $disk,
            'path' => $path,
        ];
    }

    private function deleteLogoFiles(SiteSetting $settings): void
    {
        if (! filled($settings->logo_path) || ! filled($settings->logo_disk)) {
            return;
        }

        if (! in_array($settings->logo_disk, MediaDisk::publicDisks(), true)) {
            return;
        }

        Storage::disk($settings->logo_disk)->delete($settings->logo_path);
    }
}
