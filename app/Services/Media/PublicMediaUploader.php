<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\MediaDisk;
use Imagick;
use RuntimeException;
use Throwable;

class PublicMediaUploader
{
    private const DETAIL_MAX = 1600;

    private const CARD_MAX = 640;

    private const WEBP_QUALITY = 82;

    /**
     * Store an optimized WebP cover (+ 640px card sibling).
     *
     * @return array{disk: string, path: string}
     */
    public function storeEventImage(UploadedFile $file, int $eventId): array
    {
        $disk = MediaDisk::public();
        $directory = sprintf('events/%d', $eventId);
        $uuid = Str::uuid()->toString();
        $detailPath = sprintf('%s/cover_%s.webp', $directory, $uuid);
        $cardPath = sprintf('%s/cover_%s_640.webp', $directory, $uuid);

        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Failed to read uploaded event image.');
        }

        try {
            $detailBlob = $this->encodeWebp($binary, self::DETAIL_MAX);
            $cardBlob = $this->encodeWebp($binary, self::CARD_MAX);
        } catch (Throwable $e) {
            // Fall back to storing the original if Imagick/GD cannot decode it.
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $safeExtension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';
            $filename = sprintf('cover_%s.%s', $uuid, $safeExtension);
            $path = $file->storeAs($directory, $filename, [
                'disk' => $disk,
                'visibility' => 'public',
            ]);

            if ($path === false) {
                throw new RuntimeException('Failed to store event image.', 0, $e);
            }

            return [
                'disk' => $disk,
                'path' => $path,
            ];
        }

        if (! Storage::disk($disk)->put($detailPath, $detailBlob, 'public')) {
            throw new RuntimeException('Failed to store event image.');
        }

        Storage::disk($disk)->put($cardPath, $cardBlob, 'public');

        return [
            'disk' => $disk,
            'path' => $detailPath,
        ];
    }

    public function delete(string $disk, string $path): void
    {
        if (! in_array($disk, MediaDisk::publicDisks(), true)) {
            return;
        }

        $storage = Storage::disk($disk);
        $storage->delete($path);

        $cardPath = event_image_card_path($path);
        if ($cardPath !== null && $cardPath !== $path) {
            $storage->delete($cardPath);
        }
    }

    private function encodeWebp(string $binary, int $maxEdge): string
    {
        if (extension_loaded('imagick') && class_exists(Imagick::class)) {
            return $this->encodeWithImagick($binary, $maxEdge);
        }

        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            return $this->encodeWithGd($binary, $maxEdge);
        }

        throw new RuntimeException('No image encoder available.');
    }

    private function encodeWithImagick(string $binary, int $maxEdge): string
    {
        $image = new Imagick;
        $image->readImageBlob($binary);

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }

        if ($image->getNumberImages() > 1) {
            $image = $image->coalesceImages();
            $image->setIteratorIndex(0);
        }

        $image->setImageBackgroundColor('white');
        if ($image->getImageAlphaChannel()) {
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
            $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        }

        $width = $image->getImageWidth();
        $height = $image->getImageHeight();
        if ($width > $maxEdge || $height > $maxEdge) {
            $image->thumbnailImage($maxEdge, $maxEdge, true);
        }

        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(self::WEBP_QUALITY);
        $image->stripImage();

        $blob = $image->getImageBlob();
        $image->clear();
        $image->destroy();

        if ($blob === false || $blob === '') {
            throw new RuntimeException('Imagick produced an empty WebP.');
        }

        return $blob;
    }

    private function encodeWithGd(string $binary, int $maxEdge): string
    {
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('GD could not decode the upload.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxEdge / max($width, $height, 1));
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetW, $targetH);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
        imagedestroy($source);

        if (! function_exists('imagewebp')) {
            imagedestroy($canvas);
            throw new RuntimeException('GD WebP support is missing.');
        }

        ob_start();
        imagewebp($canvas, null, self::WEBP_QUALITY);
        $blob = ob_get_clean();
        imagedestroy($canvas);

        if ($blob === false || $blob === '') {
            throw new RuntimeException('GD produced an empty WebP.');
        }

        return $blob;
    }
}
