<?php

namespace App\Http\Controllers\Web\Legal;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\View\View;

class AccountDeletionHelpController extends Controller
{
    public function __invoke(): View
    {
        $brand = site_display_name();
        $days = (int) config('auth.account_deletion_grace_days', 30);
        $email = (string) __('support.email');

        $replace = static fn (string $text): string => str_replace(
            [':brand', ':days', ':email'],
            [$brand, (string) $days, $email],
            $text,
        );

        return view('legal.account-deletion', [
            'seo' => Seo::forAccountDeletion(),
            'brand' => $brand,
            'email' => $email,
            'days' => $days,
            'intro' => $replace((string) __('account_deletion.intro')),
            'steps' => array_map($replace, __('account_deletion.steps')),
            'cancelBody' => $replace((string) __('account_deletion.cancel_body')),
            'helpBody' => $replace((string) __('account_deletion.help_body')),
        ]);
    }
}
