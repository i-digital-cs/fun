<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Support\MediaDisk;

class PublicMediaUploader
{
    /**
     * @return array{disk: string, path: string}
     */
    public function storeEventImage(UploadedFile $file, int $eventId): array
    {
        $disk = MediaDisk::public();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
        $directory = sprintf('events/%d', $eventId);
        $filename = sprintf('cover_%s.%s', Str::uuid()->toString(), $safeExtension);

        $path = $file->storeAs($directory, $filename, [
            'disk' => $disk,
            'visibility' => 'public',
        ]);

        if ($path === false) {
            throw new \RuntimeException('Failed to store event image.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
        ];
    }

    public function delete(string $disk, string $path): void
    {
        if (! in_array($disk, MediaDisk::publicDisks(), true)) {
            return;
        }

        \Illuminate\Support\Facades\Storage::disk($disk)->delete($path);
    }
}
