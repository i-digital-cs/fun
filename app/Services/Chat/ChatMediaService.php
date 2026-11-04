<?php

namespace App\Services\Chat;

use App\Exceptions\DomainException;
use App\Services\Media\MediaUploadValidator;
use App\Support\MediaDisk;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatMediaService
{
    public const MAX_VOICE_BYTES = 5_242_880; // 5 MB

    public const MAX_VOICE_MS = 120_000;

    /** Reject empty / truncated recorder blobs before they poison the thread. */
    public const MIN_VOICE_BYTES = 256;

    public const MAX_IMAGE_BYTES = 8_388_608; // 8 MB

    public const MAX_VIDEO_BYTES = 52_428_800; // 50 MB

    public const MAX_FILE_BYTES = 10_485_760; // 10 MB

    /** @var list<string> */
    public const VOICE_MIMES = [
        'audio/webm',
        // Browsers / fileinfo often label Opus-in-WebM as video/webm.
        'video/webm',
        'audio/ogg',
        'video/ogg',
        'audio/mpeg',
        'audio/mp4',
        'audio/m4a',
        'audio/x-m4a',
        'audio/aac',
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

    /** @var list<string> */
    public const VIDEO_MIMES = [
        'video/mp4',
        'video/webm',
        'video/quicktime',
    ];

    public function __construct(
        private readonly MediaUploadValidator $validator,
    ) {}

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeVoice(UploadedFile $file, int $userId, ?int $durationMs = null, ?int $scopeId = null, string $scope = 'user'): array
    {
        $meta = $this->validator->assertCategory($file, 'chat_voice');

        if ($durationMs !== null && ($durationMs < 1 || $durationMs > self::MAX_VOICE_MS)) {
            throw new DomainException(__('chat.voice_too_long'));
        }

        return $this->store($file, $userId, 'voice', $durationMs, 'voice', $meta, $scopeId, $scope);
    }

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeImage(UploadedFile $file, int $userId, ?int $scopeId = null, string $scope = 'user'): array
    {
        $meta = $this->validator->assertCategory($file, 'chat_image');

        return $this->store($file, $userId, 'image', null, 'image', $meta, $scopeId, $scope);
    }

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeVideo(UploadedFile $file, int $userId, ?int $scopeId = null, string $scope = 'user'): array
    {
        $meta = $this->validator->assertCategory($file, 'chat_video');

        return $this->store($file, $userId, 'video', null, 'video', $meta, $scopeId, $scope);
    }

    /**
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    public function storeFile(UploadedFile $file, int $userId, ?int $scopeId = null, string $scope = 'user'): array
    {
        $mime = $this->validator->detectMime($file);
        if (str_starts_with($mime, 'image/')) {
            return $this->storeImage($file, $userId, $scopeId, $scope);
        }
        if (in_array($mime, self::VIDEO_MIMES, true)) {
            return $this->storeVideo($file, $userId, $scopeId, $scope);
        }
        if (str_starts_with($mime, 'audio/') || in_array($mime, ['video/webm', 'video/ogg'], true)) {
            return $this->storeVoice($file, $userId, null, $scopeId, $scope);
        }

        $meta = $this->validator->assertCategory($file, 'chat_file');

        return $this->store($file, $userId, 'file', null, 'file', $meta, $scopeId, $scope);
    }

    /**
     * Stream private chat media with HTTP Range support (required for reliable
     * browser / Safari audio seeking and playback).
     */
    public function stream(string $path, string $mime, ?Request $request = null): StreamedResponse|Response
    {
        $diskName = MediaDisk::private();
        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404);
        abort_unless($this->isAllowedChatObjectKey($path), 404);

        $mime = $this->baseMime($mime) ?: 'application/octet-stream';
        $size = (int) $disk->size($path);
        if ($size < 1) {
            // Storage::fake() and some remote drivers can report size()=0.
            $size = strlen((string) $disk->get($path));
        }
        abort_if($size < 1, 404);

        $request ??= request();
        $rangeHeader = (string) $request->header('Range', '');
        $cache = (string) config('media.private_cache_control', 'private, no-store, no-transform');

        if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
            $start = $m[1] !== '' ? (int) $m[1] : 0;
            $end = $m[2] !== '' ? (int) $m[2] : ($size - 1);
            if ($start > $end || $start >= $size) {
                return response('', 416, [
                    'Content-Range' => 'bytes */'.$size,
                    'Accept-Ranges' => 'bytes',
                ]);
            }
            $end = min($end, $size - 1);
            $length = $end - $start + 1;

            return $this->audioStreamResponse(function () use ($disk, $path, $start, $length) {
                $this->emitRangeBytes($disk, $path, $start, $length);
            }, 206, [
                'Content-Type' => $mime,
                'Content-Length' => (string) $length,
                'Content-Range' => "bytes {$start}-{$end}/{$size}",
                'Accept-Ranges' => 'bytes',
                'Content-Disposition' => 'inline',
                'Cache-Control' => $cache,
            ]);
        }

        return $this->audioStreamResponse(function () use ($disk, $path) {
            $stream = $disk->readStream($path);
            if (! is_resource($stream)) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) $size,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline',
            'Cache-Control' => $cache,
        ]);
    }

    /**
     * @param  callable(): void  $callback
     * @param  array<string, string>  $headers
     */
    private function audioStreamResponse(callable $callback, int $status, array $headers): StreamedResponse
    {
        // Prevent reverse proxies / Apache mod_deflate from rewriting Content-Length
        // out from under browser audio players (especially Safari + Range).
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
            @apache_setenv('dont-vary', '1');
        }

        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['X-Accel-Buffering'] = 'no';
        $cache = (string) ($headers['Cache-Control'] ?? 'private, no-store');
        if (! str_contains($cache, 'no-transform')) {
            $cache .= ', no-transform';
        }
        $headers['Cache-Control'] = $cache;

        return response()->stream($callback, $status, $headers);
    }

    private function emitRangeBytes(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $path, int $start, int $length): void
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            echo substr((string) $disk->get($path), $start, $length);

            return;
        }

        $meta = stream_get_meta_data($stream);
        $seekable = (bool) ($meta['seekable'] ?? false);
        $positioned = $start === 0;

        if ($start > 0 && $seekable && fseek($stream, $start) === 0) {
            $positioned = true;
        }

        if (! $positioned && $start > 0) {
            $skipped = 0;
            while ($skipped < $start && ! feof($stream)) {
                $chunk = fread($stream, min(8192, $start - $skipped));
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $skipped += strlen($chunk);
            }
        }

        $remaining = $length;
        while ($remaining > 0 && ! feof($stream)) {
            $chunk = fread($stream, min(8192, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
        }
        fclose($stream);
    }

    /**
     * @param  array{mime: string, extension: string, bytes: int}  $meta
     * @return array{path: string, mime: string, bytes: int, duration_ms: int|null, message_type: string}
     */
    private function store(
        UploadedFile $file,
        int $userId,
        string $kind,
        ?int $durationMs,
        string $messageType,
        array $meta,
        ?int $scopeId,
        string $scope,
    ): array {
        if (! ChatV2Capabilities::directMessageExtensions() && ! ChatV2Capabilities::eventMessageExtensions()) {
            throw new DomainException(__('chat.media_unavailable'));
        }

        $disk = MediaDisk::private();
        $ext = $meta['extension'] ?: 'bin';
        $mime = $meta['mime'];

        // Prefer scoped layout; keep legacy chat/{userId}/{kind}/ for callers without scope.
        if ($scope === 'direct' && $scopeId) {
            $directory = sprintf('chat/direct/%d', $scopeId);
        } elseif ($scope === 'event' && $scopeId) {
            $directory = sprintf('chat/events/%d', $scopeId);
        } else {
            $directory = sprintf('chat/%d/%s', $userId, $kind);
        }

        $filename = Str::uuid()->toString().'.'.$ext;

        $path = $file->storeAs($directory, $filename, [
            'disk' => $disk,
            'visibility' => 'private',
        ]);

        if ($path === false) {
            throw new DomainException(__('chat.attachment_failed'));
        }

        // Verify actual stored bytes (not UploadedFile::fake claimed sizes).
        $storedSize = strlen((string) Storage::disk($disk)->get($path));
        if ($storedSize < 1) {
            $storedSize = (int) Storage::disk($disk)->size($path);
        }
        if ($messageType === 'voice' && $storedSize < self::MIN_VOICE_BYTES) {
            Storage::disk($disk)->delete($path);
            throw new DomainException(__('chat.voice_empty'));
        }

        return [
            'path' => $path,
            'mime' => $this->playbackMime($mime, $messageType),
            'bytes' => $storedSize,
            'duration_ms' => $durationMs,
            'message_type' => $messageType,
        ];
    }

    private function isAllowedChatObjectKey(string $path): bool
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        return str_starts_with($path, 'chat/');
    }

    private function baseMime(string $mime): string
    {
        return strtolower(trim(explode(';', $mime, 2)[0]));
    }

    /**
     * Normalize container MIME so <audio> / audioplayers get an audio/* type.
     */
    private function playbackMime(string $mime, string $messageType): string
    {
        $mime = $this->baseMime($mime) ?: 'application/octet-stream';
        if ($messageType !== 'voice') {
            return $mime;
        }

        return match ($mime) {
            'video/webm' => 'audio/webm',
            'video/ogg' => 'audio/ogg',
            'audio/m4a', 'audio/x-m4a', 'audio/aac' => 'audio/mp4',
            default => $mime,
        };
    }
}
