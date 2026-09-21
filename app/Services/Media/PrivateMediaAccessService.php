<?php

namespace App\Services\Media;

use App\Models\IdentityDocument;
use App\Support\MediaDisk;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateMediaAccessService
{
    public function stream(IdentityDocument $document): StreamedResponse
    {
        MediaDisk::assertPrivateDisk($document->disk);

        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->path), 404);

        return $disk->response(
            $document->path,
            basename($document->path),
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }
}
