<?php

namespace App\Support;

use InvalidArgumentException;

final class MediaDisk
{
    /**
     * @return list<string>
     */
    public static function publicDisks(): array
    {
        return array_values(config('media.public_disks', ['media', 'r2']));
    }

    /**
     * @return list<string>
     */
    public static function privateDisks(): array
    {
        return array_values(config('media.private_disks', ['media_private', 'r2_private']));
    }

    public static function public(): string
    {
        $disk = (string) config('media.disk', 'media');

        if (! in_array($disk, self::publicDisks(), true)) {
            throw new InvalidArgumentException("Invalid public media disk [{$disk}].");
        }

        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            throw new InvalidArgumentException("Filesystem disk [{$disk}] is not configured.");
        }

        return $disk;
    }

    public static function private(): string
    {
        $disk = (string) config('media.private_disk', 'media_private');

        if (! in_array($disk, self::privateDisks(), true)) {
            throw new InvalidArgumentException("Invalid private media disk [{$disk}].");
        }

        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            throw new InvalidArgumentException("Filesystem disk [{$disk}] is not configured.");
        }

        return $disk;
    }

    public static function assertPrivateDisk(string $disk): void
    {
        if (! in_array($disk, self::privateDisks(), true)) {
            throw new InvalidArgumentException("Disk [{$disk}] is not an allowed private media disk.");
        }
    }
}
