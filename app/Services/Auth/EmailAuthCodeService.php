<?php

namespace App\Services\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Mail\AuthCodeMail;
use App\Models\EmailAuthCode;
use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailAuthCodeService
{
    public function __construct(private readonly SiteSettingsService $siteSettings)
    {
    }

    /**
     * Issue a fresh 4-digit code, invalidate prior ones, and email it.
     */
    public function issueAndSend(User $user, EmailAuthPurpose $purpose): void
    {
        EmailAuthCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        EmailAuthCode::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new AuthCodeMail(
            user: $user,
            code: $code,
            purpose: $purpose->value,
            siteName: $this->siteSettings->displayName(),
            logoUrl: $this->siteSettings->logoUrl(),
        ));
    }

    /**
     * @throws DomainException
     */
    public function verify(User $user, EmailAuthPurpose $purpose, string $code): void
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';

        if (strlen($code) !== 4) {
            throw new DomainException(__('auth.invalid_code'));
        }

        $record = EmailAuthCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($record === null || $record->isExpired()) {
            throw new DomainException(__('auth.code_expired'));
        }

        if (! Hash::check($code, $record->code_hash)) {
            throw new DomainException(__('auth.invalid_code'));
        }

        $record->forceFill(['consumed_at' => now()])->save();
    }
}
