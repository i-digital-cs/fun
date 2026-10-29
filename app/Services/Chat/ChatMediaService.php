<?php

namespace App\Services\Chat;

use App\Exceptions\DomainException;
use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatMediaService
{
    public const MAX_VOICE_BYTES = 5_242_880; // 5 MB

    public const MAX_VOICE_MS = 120_000;

    public const MAX_IMAGE_BYTES = 8_388_608; // 8 MB

    public const MAX_FILE_BYTES = 10_485_760; // 10 MB

    /** @var list<string> */
    public const VOICE_MIMES = [
        'audio/webm',
        'audio/ogg',
        'audio/mpeg',
        'audio/mp4',
        'audio/x-m4a',
        'audio/wav',
        'audio/wave',
        'audio/x-wav',
    ];

    /** @var list<string> */
    public const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeVoice(UploadedFile $file, int $userId, ?int $durationMs = null): array
    {
        $this->assertSafeUpload($file, self::MAX_VOICE_BYTES, self::VOICE_MIMES);

        if ($durationMs !== null && ($durationMs < 1 || $durationMs > self::MAX_VOICE_MS)) {
            throw new DomainException(__('chat.voice_too_long'));
        }

        return $this->store($file, $userId, 'voice', $durationMs, 'voice');
    }

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeImage(UploadedFile $file, int $userId): array
    {
        $this->assertSafeUpload($file, self::MAX_IMAGE_BYTES, self::IMAGE_MIMES);

        return $this->store($file, $userId, 'image', null, 'image');
    }

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeFile(UploadedFile $file, int $userId): array
    {
        $mime = (string) ($file->getMimeType() ?: '');
        if (str_starts_with($mime, 'image/')) {
            return $this->storeImage($file, $userId);
        }
        if (str_starts_with($mime, 'audio/')) {
            return $this->storeVoice($file, $userId);
        }

        // Generic docs only — block executables.
        $blocked = ['application/x-msdownload', 'application/x-executable', 'application/x-sh', 'text/x-shellscript'];
        if (in_array($mime, $blocked, true) || preg_match('/\.(exe|bat|cmd|sh|php|js|msi|apk)$/i', $file->getClientOriginalName() ?? '')) {
            throw new DomainException(__('chat.attachment_forbidden'));
        }

        $this->assertSafeUpload($file, self::MAX_FILE_BYTES, null);

        return $this->store($file, $userId, 'file', null, 'file');
    }

    public function stream(string $path, string $mime): StreamedResponse
    {
        $diskName = MediaDisk::private();
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404);
        abort_unless(str_starts_with($path, 'chat/'), 404);

        return $disk->response($path, basename($path), [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @param  list<string>|null  $allowedMimes
     */
    private function assertSafeUpload(UploadedFile $file, int $maxBytes, ?array $allowedMimes): void
    {
        if (! $file->isValid()) {
            throw new DomainException(__('chat.attachment_invalid'));
        }
        if ((int) $file->getSize() > $maxBytes) {
            throw new DomainException(__('chat.attachment_too_large'));
        }
        $mime = (string) ($file->getMimeType() ?: '');
        if ($allowedMimes !== null && ! in_array($mime, $allowedMimes, true)) {
            throw new DomainException(__('chat.attachment_type'));
        }
    }

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    private function store(
        UploadedFile $file,
        int $userId,
        string $kind,
        ?int $durationMs,
        string $messageType,
    ): array {
        if (! ChatV2Capabilities::directMessageExtensions() && ! ChatV2Capabilities::eventMessageExtensions()) {
            throw new DomainException(__('chat.media_unavailable'));
        }

        $disk = MediaDisk::private();
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'bin';
        $directory = sprintf('chat/%d/%s', $userId, $kind);
        $filename = Str::uuid()->toString().'.'.$ext;

        $path = $file->storeAs($directory, $filename, [
            'disk' => $disk,
            'visibility' => 'private',
        ]);

        if ($path === false) {
            throw new DomainException(__('chat.attachment_failed'));
        }

        return [
            'path' => $path,
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'bytes' => (int) $file->getSize(),
            'duration_ms' => $durationMs,
            'message_type' => $messageType,
        ];
    }
}
