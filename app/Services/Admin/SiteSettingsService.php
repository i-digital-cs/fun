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

    public const APP_CACHE_KEY = 'site.app_version';

    public function __construct(private readonly AdminAuditLogger $audit)
    {
    }

    public function current(): SiteSetting
    {
        $settings = SiteSetting::query()->first();

        if ($settings === null) {
            $settings = SiteSetting::query()->create([
                'display_name' => (string) config('app.name', 'Community Platform'),
                'android_latest_version' => '1.0.0',
                'android_min_version' => '1.0.0',
                'ios_latest_version' => '1.0.0',
                'ios_min_version' => '1.0.0',
                'force_update' => false,
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

            if (filled($settings->logo_path) && filled($settings->logo_disk)) {
                try {
                    return Storage::disk($settings->logo_disk)->url($settings->logo_path);
                } catch (\Throwable) {
                    // Fall through to bundled official mark.
                }
            }

            return asset('branding/funity-mark.svg');
        });
    }

    /**
     * Attach the official Funity mark into site settings storage (admin + GET /api/site).
     */
    public function attachOfficialLogo(bool $force = false): SiteSetting
    {
        $settings = $this->current();
        if (! $force && filled($settings->logo_path) && filled($settings->logo_disk)) {
            return $settings;
        }

        $source = public_path('branding/funity-mark.svg');
        if (! is_file($source)) {
            return $settings;
        }

        $disk = MediaDisk::public();
        $path = 'branding/funity-mark.svg';
        Storage::disk($disk)->put($path, (string) file_get_contents($source), 'public');

        if ($force) {
            $this->deleteLogoFiles($settings);
        }

        $settings->forceFill([
            'logo_path' => $path,
            'logo_disk' => $disk,
        ])->save();

        Cache::forget(self::LOGO_CACHE_KEY);

        return $settings->refresh();
    }

    /**
     * Public payload for Flutter / clients (version + force update).
     *
     * @return array<string, mixed>
     */
    public function appUpdatePayload(?string $locale = null): array
    {
        $payload = Cache::remember(self::APP_CACHE_KEY, now()->addMinutes(5), function () {
            $s = $this->current();

            return [
                'force_update' => (bool) $s->force_update,
                'message_en' => $s->update_message_en,
                'message_ar' => $s->update_message_ar,
                'android' => [
                    'latest' => $s->android_latest_version ?: '1.0.0',
                    'min' => $s->android_min_version ?: '1.0.0',
                    'store_url' => $s->android_store_url,
                ],
                'ios' => [
                    'latest' => $s->ios_latest_version ?: '1.0.0',
                    'min' => $s->ios_min_version ?: '1.0.0',
                    'store_url' => $s->ios_store_url,
                ],
            ];
        });

        $locale = $locale ?: app()->getLocale();
        $message = $locale === 'ar'
            ? ($payload['message_ar'] ?: $payload['message_en'])
            : ($payload['message_en'] ?: $payload['message_ar']);

        return [
            'force_update' => $payload['force_update'],
            'message' => $message,
            'android' => $payload['android'],
            'ios' => $payload['ios'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, array $data): SiteSetting
    {
        if ($actor->platform_role !== PlatformRole::SuperAdmin) {
            throw new DomainException(__('admin.super_admin_only'));
        }

        $clean = ChatMessageSanitizer::clean((string) ($data['display_name'] ?? ''));

        if ($clean === '' || mb_strlen($clean) > 80) {
            throw new DomainException(__('admin.invalid_display_name'));
        }

        $settings = $this->current();
        $previous = $settings->only([
            'display_name',
            'logo_path',
            'android_latest_version',
            'android_min_version',
            'ios_latest_version',
            'ios_min_version',
            'force_update',
        ]);

        $fill = [
            'display_name' => $clean,
            'updated_by' => $actor->id,
        ];

        foreach (['android_latest_version', 'android_min_version', 'ios_latest_version', 'ios_min_version'] as $versionKey) {
            if (array_key_exists($versionKey, $data)) {
                $fill[$versionKey] = $this->normalizeVersion($data[$versionKey] ?? $settings->{$versionKey} ?: '1.0.0');
            }
        }

        if (array_key_exists('force_update', $data)) {
            $fill['force_update'] = (bool) $data['force_update'];
        }

        foreach (['android_store_url', 'ios_store_url'] as $urlKey) {
            if (array_key_exists($urlKey, $data)) {
                $fill[$urlKey] = $this->nullableUrl($data[$urlKey]);
            }
        }

        foreach (['update_message_en', 'update_message_ar'] as $msgKey) {
            if (array_key_exists($msgKey, $data)) {
                $fill[$msgKey] = $this->nullableText($data[$msgKey]);
            }
        }

        $settings->forceFill($fill);

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
        Cache::forget(self::APP_CACHE_KEY);

        $this->audit->log($actor, 'site_settings.updated', SiteSetting::class, $settings->id, [
            'previous' => $previous,
            'display_name' => $clean,
            'force_update' => (bool) $settings->force_update,
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

    private function normalizeVersion(mixed $value): string
    {
        $v = trim((string) $value);
        if ($v === '' || ! preg_match('/^\d+(\.\d+){0,3}$/', $v)) {
            throw new DomainException(__('admin.invalid_app_version'));
        }

        return $v;
    }

    private function nullableUrl(mixed $value): ?string
    {
        $v = trim((string) $value);

        return $v === '' ? null : $v;
    }

    private function nullableText(mixed $value): ?string
    {
        $v = trim(ChatMessageSanitizer::clean((string) $value));

        return $v === '' ? null : mb_substr($v, 0, 500);
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
