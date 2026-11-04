<?php

namespace Tests\Unit;

use App\Exceptions\DomainException;
use App\Services\Media\MediaUploadValidator;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MediaUploadValidatorTest extends TestCase
{
    public function test_accepts_valid_chat_image(): void
    {
        $validator = app(MediaUploadValidator::class);
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $meta = $validator->assertCategory($file, 'chat_image');

        $this->assertSame('jpg', $meta['extension']);
        $this->assertGreaterThan(0, $meta['bytes']);
        $this->assertStringStartsWith('image/', $meta['mime']);
    }

    public function test_rejects_svg_for_chat_image_category(): void
    {
        $validator = app(MediaUploadValidator::class);
        $file = UploadedFile::fake()->create('x.svg', 10, 'image/svg+xml');

        $this->expectException(DomainException::class);
        $validator->assertCategory($file, 'chat_image');
    }

    public function test_rejects_executable_as_chat_file(): void
    {
        $validator = app(MediaUploadValidator::class);
        $file = UploadedFile::fake()->create('evil.exe', 20, 'application/x-msdownload');

        $this->expectException(DomainException::class);
        $validator->assertCategory($file, 'chat_file');
    }

    public function test_rejects_oversized_profile_image(): void
    {
        $validator = app(MediaUploadValidator::class);
        // profile max 5MB; fake size is KB
        $file = UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg');

        $this->expectException(DomainException::class);
        $validator->assertCategory($file, 'profile');
    }
}
