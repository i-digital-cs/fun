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

        $mime = (string) ($document->mime_type ?: 'application/octet-stream');
        // Never allow active content types through the identity gateway.
        if (preg_match('#^(text/html|image/svg\\+xml|application/(javascript|x-javascript)|text/javascript)#i', $mime)) {
            abort(404);
        }

        $cache = (string) config('media.private_cache_control', 'private, no-store, no-transform');

        return $disk->response(
            $document->path,
            basename($document->path),
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline',
                'Cache-Control' => $cache,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
