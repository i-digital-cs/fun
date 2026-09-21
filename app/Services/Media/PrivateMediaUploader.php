<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivateMediaUploader
{
    /**
     * @return array{disk: string, path: string, mime_type: string, size: int}
     */
    public function storeIdentityDocument(UploadedFile $file, int $userId, string $type): array
    {
        $disk = (string) config('media.private_disk');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
        $directory = sprintf('identity/%d', $userId);
        $filename = sprintf('%s_%s.%s', $type, Str::uuid()->toString(), $safeExtension);

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
            'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
        ];
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }
}
