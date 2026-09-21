<?php

namespace Tests\Unit;

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
    }
}
