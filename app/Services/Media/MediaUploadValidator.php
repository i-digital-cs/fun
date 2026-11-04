<?php

namespace App\Services\Media;

use App\Exceptions\DomainException;
use Illuminate\Http\UploadedFile;

/**
 * Centralized server-side upload validation.
 * Never trusts the browser-provided MIME type alone.
 */
class MediaUploadValidator
{
    /**
     * @return array{mime: string, extension: string, bytes: int}
     */
    public function assertCategory(UploadedFile $file, string $category): array
    {
        /** @var array<string, mixed>|null $policy */
        $policy = config("media.categories.{$category}");
        if (! is_array($policy)) {
            throw new DomainException(__('chat.attachment_invalid'));
        }

        if (! $file->isValid()) {
            throw new DomainException(__('chat.attachment_invalid'));
        }

        $bytes = (int) $file->getSize();
        $min = (int) ($policy['min_bytes'] ?? 1);
        $max = (int) ($policy['max_bytes'] ?? 0);

        if ($bytes < $min) {
            throw new DomainException(__('chat.voice_empty'));
        }
        if ($max > 0 && $bytes > $max) {
            throw new DomainException(__('chat.attachment_too_large'));
        }

        $detected = $this->detectMime($file);
        $clientName = (string) ($file->getClientOriginalName() ?? '');

        if ($this->looksTraversable($clientName) || $this->looksTraversable($file->getPathname())) {
            throw new DomainException(__('chat.attachment_forbidden'));
        }

        /** @var list<string> $blockedExt */
        $blockedExt = array_values($policy['blocked_extensions'] ?? []);
        /** @var list<string> $blockedMimes */
        $blockedMimes = array_values($policy['blocked_mimes'] ?? []);

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: '';

        if ($blockedExt !== [] && $ext !== '' && in_array($ext, $blockedExt, true)) {
            throw new DomainException(__('chat.attachment_forbidden'));
        }
        if ($blockedMimes !== [] && in_array($detected, $blockedMimes, true)) {
            throw new DomainException(__('chat.attachment_forbidden'));
        }
        if (preg_match('/\.(exe|bat|cmd|sh|php|phtml|js|msi|apk|svg|html?)$/i', $clientName)) {
            throw new DomainException(__('chat.attachment_forbidden'));
        }

        /** @var list<string>|null $allowedMimes */
        $allowedMimes = isset($policy['mimes']) ? array_values($policy['mimes']) : null;
        if ($allowedMimes !== null && ! in_array($detected, $allowedMimes, true)) {
            if (str_contains($detected, 'heic') || str_contains($detected, 'heif')) {
                throw new DomainException(__('chat.attachment_heic'));
            }
            throw new DomainException(__('chat.attachment_type'));
        }

        /** @var list<string> $allowedExt */
        $allowedExt = array_values($policy['extensions'] ?? []);
        if ($allowedExt !== [] && $ext !== '' && ! in_array($ext, $allowedExt, true)) {
            // Prefer extension derived from detected MIME below.
            $ext = '';
        }

        $ext = $this->extensionForMime($detected, $ext !== '' ? $ext : 'bin');

        return [
            'mime' => $detected,
            'extension' => $ext,
            'bytes' => $bytes,
        ];
    }

    public function detectMime(UploadedFile $file): string
    {
        $path = $file->getRealPath() ?: $file->getPathname();
        $mime = '';

        if (is_string($path) && $path !== '' && is_file($path)) {
            if (class_exists(\finfo::class)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = (string) $finfo->file($path);
            } elseif (function_exists('mime_content_type')) {
                $mime = (string) mime_content_type($path);
            }
        }

        if ($mime === '' || $mime === 'application/octet-stream') {
            $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');
        }

        return strtolower(trim(explode(';', $mime, 2)[0]));
    }

    public function extensionForMime(string $mime, string $fallback = 'bin'): string
    {
        return match ($mime) {
            'audio/webm', 'video/webm' => 'webm',
            'audio/ogg', 'video/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'audio/aac' => 'm4a',
            'audio/wav', 'audio/wave', 'audio/x-wav' => 'wav',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'application/pdf' => 'pdf',
            default => preg_replace('/[^a-z0-9]/', '', strtolower($fallback)) ?: 'bin',
        };
    }

    private function looksTraversable(string $value): bool
    {
        return str_contains($value, '..') || str_contains($value, "\0");
    }
}
