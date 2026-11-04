<?php

namespace App\Services\Media;

use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivateMediaUploader
{
    public function __construct(
        private readonly MediaUploadValidator $validator,
    ) {}

    /**
     * @return array{disk: string, path: string, mime_type: string, size: int}
     */
    public function storeIdentityDocument(UploadedFile $file, int $userId, string $type): array
    {
        $meta = $this->validator->assertCategory($file, 'identity');
        $disk = MediaDisk::private();
        $safeType = preg_replace('/[^a-z0-9_]/', '', strtolower($type)) ?: 'doc';
        $directory = sprintf('identity/%d', $userId);
        $filename = sprintf('%s_%s.%s', $safeType, Str::uuid()->toString(), $meta['extension']);

        $path = $file->storeAs($directory, $filename, [
            'disk' => $disk,
            'visibility' => 'private',
        ]);

        if ($path === false) {
            throw new \RuntimeException('Failed to store identity document.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $meta['mime'],
            'size' => $meta['bytes'],
        ];
    }

    public function delete(string $disk, string $path): void
    {
        MediaDisk::assertPrivateDisk($disk);

        Storage::disk($disk)->delete($path);
    }
}
