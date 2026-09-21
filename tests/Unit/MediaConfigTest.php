<?php

namespace Tests\Unit;

use App\Support\MediaDisk;
use InvalidArgumentException;
use Tests\TestCase;

class MediaConfigTest extends TestCase
{
    public function test_media_disks_are_configured_for_local_scaffold(): void
    {
        $this->assertSame('media', config('media.disk'));
        $this->assertSame('media_private', config('media.private_disk'));
        $this->assertArrayHasKey('media', config('filesystems.disks'));
        $this->assertArrayHasKey('media_private', config('filesystems.disks'));
        $this->assertArrayHasKey('r2', config('filesystems.disks'));
        $this->assertArrayHasKey('r2_private', config('filesystems.disks'));
        $this->assertSame('s3', config('filesystems.disks.r2.driver'));
        $this->assertSame('s3', config('filesystems.disks.r2_private.driver'));
        $this->assertSame('private', config('filesystems.disks.r2_private.visibility'));
    }

    public function test_media_disk_helper_resolves_configured_disks(): void
    {
        $this->assertSame('media', MediaDisk::public());
        $this->assertSame('media_private', MediaDisk::private());
    }

    public function test_media_disk_helper_rejects_invalid_public_disk(): void
    {
        config(['media.disk' => 'not_a_disk']);

        $this->expectException(InvalidArgumentException::class);
        MediaDisk::public();
    }

    public function test_switching_to_r2_disk_names_is_accepted(): void
    {
        config([
            'media.disk' => 'r2',
            'media.private_disk' => 'r2_private',
        ]);

        $this->assertSame('r2', MediaDisk::public());
        $this->assertSame('r2_private', MediaDisk::private());
    }
}
