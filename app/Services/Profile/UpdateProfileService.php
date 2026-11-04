<?php

namespace App\Services\Profile;

use App\Enums\EmailAuthPurpose;
use App\Enums\OutboundOtpLane;
use App\Enums\UserSex;
use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\Auth\EmailAuthCodeService;
use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateProfileService
{
    public function __construct(private readonly EmailAuthCodeService $codes)
    {
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     sex: string|UserSex,
     *     locale?: string,
     *     current_password?: string|null,
     *     password?: string|null,
     *     photo?: UploadedFile|null,
     *     remove_photo?: bool
     * }  $data
     * @return array{user: User, email_changed: bool}
     */
    public function update(User $user, array $data): array
    {
        $email = strtolower($data['email']);
        $emailChanged = $email !== strtolower($user->email);

        if ($emailChanged && User::query()->where('email', $email)->where('id', '!=', $user->id)->exists()) {
            throw new DomainException(__('profile.email_taken'));
        }

        if (filled($data['password'] ?? null)) {
            if (filled($user->password)) {
                if (! filled($data['current_password'] ?? null) || ! Hash::check((string) $data['current_password'], $user->password)) {
                    throw new DomainException(__('profile.current_password_invalid'));
                }
            }
        }

        $sex = $data['sex'] instanceof UserSex
            ? $data['sex']
            : UserSex::from((string) $data['sex']);

        $payload = [
            'name' => $data['name'],
            'email' => $email,
            'sex' => $sex,
        ];

        if (isset($data['locale']) && in_array($data['locale'], ['en', 'ar'], true)) {
            $payload['locale'] = $data['locale'];
        }

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        if (! empty($data['remove_photo'])) {
            $this->deleteLocalPhoto($user);
            $payload['profile_image_path'] = null;
        }

        if (($data['photo'] ?? null) instanceof UploadedFile) {
            $this->deleteLocalPhoto($user);
            $payload['profile_image_path'] = $this->storePhoto($user, $data['photo']);
        }

        if ($emailChanged) {
            $payload['email_verified_at'] = null;
        }

        $user->forceFill($payload)->save();

        if ($emailChanged) {
            $this->codes->issueAndSend(
                $user->fresh(),
                EmailAuthPurpose::EmailVerify,
                OutboundOtpLane::Member,
                mailContext: 'email_change',
            );
        }

        return [
            'user' => $user->fresh(),
            'email_changed' => $emailChanged,
        ];
    }

    private function storePhoto(User $user, UploadedFile $file): string
    {
        $meta = app(\App\Services\Media\MediaUploadValidator::class)->assertCategory($file, 'profile');
        $disk = MediaDisk::public();
        $directory = sprintf('profiles/%d', (int) $user->id);
        $filename = sprintf('%s.%s', Str::uuid()->toString(), $meta['extension']);

        $path = $file->storeAs($directory, $filename, [
            'disk' => $disk,
            'visibility' => 'public',
        ]);

        if ($path === false) {
            throw new DomainException(__('profile.photo_upload_failed'));
        }

        return Storage::disk($disk)->url($path);
    }

    private function deleteLocalPhoto(User $user): void
    {
        $url = $user->profile_image_path;

        if (! filled($url) || ! str_contains($url, '/storage/media/')) {
            return;
        }

        $path = ltrim(parse_url($url, PHP_URL_PATH) ?: '', '/');
        // URL like /storage/media/avatars/x.jpg → path avatars/x.jpg on media disk
        if (str_starts_with($path, 'storage/media/')) {
            $relative = substr($path, strlen('storage/media/'));
            Storage::disk(MediaDisk::public())->delete($relative);
        }
    }
}
