<?php

namespace App\Console\Commands;

use App\Models\OrderFile;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickException;

class ConvertOrderImagesToJpeg extends Command
{
    protected $signature = 'orders:convert-images-to-jpeg {--dry-run : Show what would be converted without making changes}';

    protected $description = 'Convert TIFF/BMP/HEIC order images to JPEG so they display in browsers';

    private const NON_BROWSER_EXTENSIONS = ['tif', 'tiff', 'bmp', 'heic', 'heif'];

    private const NON_BROWSER_MIMES = [
        'image/tiff',
        'image/x-tiff',
        'image/bmp',
        'image/x-ms-bmp',
        'image/heic',
        'image/heif',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $pathsToConvert = collect();

        OrderFile::whereIn('mime_type', self::NON_BROWSER_MIMES)->pluck('path')->each(fn ($p) => $pathsToConvert->push($p));
        OrderItem::whereNotNull('image_path')
            ->where(function ($q) {
                foreach (self::NON_BROWSER_EXTENSIONS as $ext) {
                    $q->orWhere('image_path', 'like', '%.'.$ext);
                }
            })
            ->pluck('image_path')
            ->each(fn ($p) => $pathsToConvert->push($p));

        $pathsToConvert = $pathsToConvert->unique()->values();

        if ($pathsToConvert->isEmpty()) {
            $this->info('No images to convert.');

            return 0;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Converting '.$pathsToConvert->count().' image(s)...');

        $converted = 0;
        $failed = 0;

        foreach ($pathsToConvert as $path) {
            $result = $this->convertPath($path, $dryRun);
            $result ? $converted++ : $failed++;
        }

        $this->info("Done. Converted: {$converted}, Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    private function convertPath(string $path, bool $dryRun): bool
    {
        $fullPath = Storage::disk('public')->path($path);
        if (! is_file($fullPath)) {
            $this->warn("Missing file: {$path}");

            return false;
        }

        try {
            $imagick = new Imagick($fullPath);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->stripImage();
            $blob = $imagick->getImageBlob();
            $imagick->destroy();

            $newPath = preg_replace('/\.(tif|tiff|bmp|heic|heif)$/i', '.jpg', $path);
            if ($newPath === $path) {
                $newPath = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'.jpg';
            }

            if (! $dryRun) {
                Storage::disk('public')->put($newPath, $blob);
                Storage::disk('public')->delete($path);
                OrderFile::where('path', $path)->update(['path' => $newPath, 'mime_type' => 'image/jpeg']);
                OrderItem::where('image_path', $path)->update(['image_path' => $newPath]);
            }

            $this->line("  ✓ {$path} → {$newPath}");

            return true;
        } catch (ImagickException $e) {
            $this->error("  ✗ {$path}: {$e->getMessage()}");

            return false;
        }
    }
}
