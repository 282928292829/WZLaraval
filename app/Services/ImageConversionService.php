<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickException;

class ImageConversionService
{
    /** MIME types that browsers typically cannot display in <img> tags. */
    private const NON_BROWSER_IMAGE_MIMES = [
        'image/tiff',
        'image/x-tiff',
        'image/bmp',
        'image/x-ms-bmp',
        'image/heic',
        'image/heif',
    ];

    public function __construct(
        private int $jpegQuality = 90
    ) {}

    /**
     * Store an uploaded file, converting non-browser-displayable images to JPEG.
     * Returns ['path' => string, 'mime_type' => string, 'size' => int, 'original_name' => string].
     */
    public function storeForDisplay(UploadedFile $file, string $directory, string $disk = 'public'): array
    {
        $mime = strtolower($file->getMimeType() ?? '');
        $originalName = $file->getClientOriginalName();

        if ($this->shouldConvert($mime)) {
            $result = $this->convertToJpeg($file, $directory, $disk);
            if ($result !== null) {
                return array_merge($result, ['original_name' => $this->jpegNameFromOriginal($originalName)]);
            }
        }

        $path = $file->store($directory, $disk);

        return [
            'path' => $path,
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'original_name' => $originalName,
        ];
    }

    private function shouldConvert(string $mime): bool
    {
        return in_array($mime, self::NON_BROWSER_IMAGE_MIMES, true);
    }

    private function convertToJpeg(UploadedFile $file, string $directory, string $disk): ?array
    {
        try {
            $imagick = new Imagick($file->getRealPath());
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality($this->jpegQuality);
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->stripImage();

            $blob = $imagick->getImageBlob();
            $imagick->destroy();

            $filename = str()->random(40).'.jpg';
            $path = trim($directory, '/').'/'.$filename;

            Storage::disk($disk)->makeDirectory(dirname($path));
            Storage::disk($disk)->put($path, $blob);

            return [
                'path' => $path,
                'mime_type' => 'image/jpeg',
                'size' => strlen($blob),
            ];
        } catch (ImagickException $e) {
            report($e);

            return null;
        }
    }

    private function jpegNameFromOriginal(string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        return $base.'.jpg';
    }

    /**
     * Compress an existing image file in place. Only works for image MIME types.
     * Converts to JPEG at given quality. Returns new size in bytes, or null on failure.
     */
    public function compressFile(string $path, string $disk = 'public', int $quality = 55): ?int
    {
        $fullPath = Storage::disk($disk)->path($path);
        if (! file_exists($fullPath)) {
            return null;
        }

        try {
            $imagick = new Imagick($fullPath);
            $mime = strtolower($imagick->getImageMimeType() ?? '');
            if (! str_starts_with($mime, 'image/')) {
                $imagick->destroy();

                return null;
            }

            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality($quality);
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->stripImage();

            $blob = $imagick->getImageBlob();
            $imagick->destroy();

            Storage::disk($disk)->put($path, $blob);

            return strlen($blob);
        } catch (ImagickException $e) {
            report($e);

            return null;
        }
    }
}
