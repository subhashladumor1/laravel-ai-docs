<?php

declare(strict_types=1);

namespace Subhashladumor1\LaravelAiDocs\Processors;

use Subhashladumor1\LaravelAiDocs\Exceptions\FileProcessingException;

/**
 * Responsible for pre-processing image files before they are sent
 * to an AI provider. Uses Intervention Image v3 if available,
 * otherwise falls back to a pure GD implementation.
 */
class ImageProcessor
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Pre-process the image and return base-64 encoded content.
     *
     * Steps:
     *  1. Resize to max dimensions if needed (preserving aspect ratio).
     *  2. Auto-rotate based on EXIF orientation.
     *  3. Optionally enhance contrast.
     *  4. Return base64 for transmission to AI.
     *
     * @throws FileProcessingException
     * @return array{base64: string, mimeType: string}
     */
    public function processForVision(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new FileProcessingException("Image file not found: {$filePath}");
        }

        if (class_exists(\Intervention\Image\Laravel\Facades\Image::class)) {
            return $this->processWithIntervention($filePath);
        }

        return $this->processWithGD($filePath);
    }

    /**
     * Encode the raw file as base64 without any image manipulation.
     * Useful when the file is already small.
     *
     * @return array{base64: string, mimeType: string}
     */
    public function encodeRaw(string $filePath): array
    {
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new FileProcessingException("Cannot read image: {$filePath}");
        }

        return [
            'base64' => base64_encode($contents),
            'mimeType' => $this->mimeType($filePath),
        ];
    }

    // ------------------------------------------------------------------
    //  Private Helpers
    // ------------------------------------------------------------------

    /**
     * @return array{base64: string, mimeType: string}
     */
    private function processWithIntervention(string $filePath): array
    {
        /** @var \Intervention\Image\Interfaces\ImageInterface $image */
        $image = \Intervention\Image\Laravel\Facades\Image::read($filePath);

        $maxW = (int) ($this->config['max_width'] ?? 2048);
        $maxH = (int) ($this->config['max_height'] ?? 2048);

        // Scale down if needed
        [$w, $h] = [$image->width(), $image->height()];
        if ($w > $maxW || $h > $maxH) {
            $image->scaleDown($maxW, $maxH);
        }

        // Auto-rotate
        // Note: Intervention Image v3 applies EXIF rotation automatically on read.
        if ($this->config['auto_rotate'] ?? true) {
            // No action needed for v3
        }

        $quality = (int) ($this->config['quality'] ?? 90);
        $encoded = (string) $image->toJpeg($quality)->toDataUri();

        // Strip "data:image/jpeg;base64," prefix
        $base64 = substr($encoded, strpos($encoded, ',') + 1);

        return ['base64' => $base64, 'mimeType' => 'image/jpeg'];
    }

    /**
     * @return array{base64: string, mimeType: string}
     */
    private function processWithGD(string $filePath): array
    {
        [$w, $h, $type] = getimagesize($filePath);
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_GIF => imagecreatefromgif($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            default => throw new FileProcessingException("Unsupported image type for GD: {$filePath}"),
        };

        if ($source === false) {
            throw new FileProcessingException("GD could not open image: {$filePath}");
        }

        $maxW = (int) ($this->config['max_width'] ?? 2048);
        $maxH = (int) ($this->config['max_height'] ?? 2048);

        // Resize if needed
        if ($w > $maxW || $h > $maxH) {
            $ratio = min($maxW / $w, $maxH / $h);
            $newW = (int) ($w * $ratio);
            $newH = (int) ($h * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($source);
            $source = $resized;
        }

        ob_start();
        imagejpeg($source, null, (int) ($this->config['quality'] ?? 90));
        $raw = ob_get_clean();
        imagedestroy($source);

        return ['base64' => base64_encode($raw), 'mimeType' => 'image/jpeg'];
    }

    /**
     * Detect MIME type from extension.
     */
    private function mimeType(string $filePath): string
    {
        return match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'tiff', 'tif' => 'image/tiff',
            default => 'image/jpeg',
        };
    }
}
