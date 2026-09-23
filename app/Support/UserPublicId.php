<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserPublicId
{
    public const PREFIX = 'Funity-F';

    /** First issued public ID number (Funity-F001007). */
    public const START_NUMBER = 1007;

    public static function format(int $number): string
    {
        return self::PREFIX.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public static function generate(): string
    {
        return DB::transaction(function () {
            $ids = User::query()
                ->whereNotNull('public_id')
                ->lockForUpdate()
                ->pluck('public_id');

            $max = self::START_NUMBER - 1;

            foreach ($ids as $id) {
                if (preg_match('/^'.preg_quote(self::PREFIX, '/').'(\d{6})$/', (string) $id, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            }

            do {
                $max++;
                $candidate = self::format($max);
            } while (User::query()->where('public_id', $candidate)->exists());

            return $candidate;
        });
    }

    public static function isValid(string $value): bool
    {
        return (bool) preg_match('/^'.preg_quote(self::PREFIX, '/').'\d{6}$/', $value);
    }

    /** Route `{publicId}` constraint pattern. */
    public static function routePattern(): string
    {
        return 'Funity-F[0-9]{6}';
    }
}
