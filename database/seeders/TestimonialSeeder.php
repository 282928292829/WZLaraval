<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TestimonialSeeder extends Seeder
{
    /**
     * Testimonial images from https://wasetzon.com/testimonials/
     * Each entry: [sort_order, image_url, filename, name_ar, name_en]
     */
    private array $testimonials = [
        [12, 'https://wasetzon.com/wp-content/uploads/2020/10/12-862x862.png', '12-862x862.png', 'Amazon 12', 'Amazon 12'],
        [11, 'https://wasetzon.com/wp-content/uploads/2020/10/11-862x862.png', '11-862x862.png', 'Amazon 11', 'Amazon 11'],
        [10, 'https://wasetzon.com/wp-content/uploads/2020/10/10-862x862.png', '10-862x862.png', 'Amazon 10', 'Amazon 10'],
        [9, 'https://wasetzon.com/wp-content/uploads/2020/10/9-862x862.png', '9-862x862.png', 'Amazon 9', 'Amazon 9'],
        [8, 'https://wasetzon.com/wp-content/uploads/2020/10/8-862x862.png', '8-862x862.png', 'Amazon 8', 'Amazon 8'],
        [7, 'https://wasetzon.com/wp-content/uploads/2020/10/7-862x862.png', '7-862x862.png', 'Amazon 7', 'Amazon 7'],
        [6, 'https://wasetzon.com/wp-content/uploads/2020/10/6-862x862.png', '6-862x862.png', 'Amazon 6', 'Amazon 6'],
        [5, 'https://wasetzon.com/wp-content/uploads/2020/10/5-1-862x862.png', '5-1-862x862.png', 'Amazon 5', 'Amazon 5'],
        [4, 'https://wasetzon.com/wp-content/uploads/2020/10/4-862x862.png', '4-862x862.png', 'Amazon 4', 'Amazon 4'],
        [3, 'https://wasetzon.com/wp-content/uploads/2020/10/3-862x862.png', '3-862x862.png', 'Amazon 3', 'Amazon 3'],
        [2, 'https://wasetzon.com/wp-content/uploads/2020/10/2-1-862x862.png', '2-1-862x862.png', 'Amazon 2', 'Amazon 2'],
        [1, 'https://wasetzon.com/wp-content/uploads/2020/10/1-862x862.png', '1-862x862.png', 'Amazon 1', 'Amazon 1'],
    ];

    public function run(): void
    {
        $disk = Storage::disk('public');
        $dir = 'testimonials';
        $disk->makeDirectory($dir);

        foreach ($this->testimonials as [$sortOrder, $url, $filename, $nameAr, $nameEn]) {
            $path = "{$dir}/{$filename}";

            if ($disk->exists($path)) {
                $this->upsertTestimonial($path, $sortOrder, $nameAr, $nameEn);

                continue;
            }

            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                $this->command?->warn("Failed to download {$url}: HTTP {$response->status()}");

                continue;
            }

            $disk->put($path, $response->body());
            $this->upsertTestimonial($path, $sortOrder, $nameAr, $nameEn);
        }
    }

    private function upsertTestimonial(string $imagePath, int $sortOrder, string $nameAr, string $nameEn): void
    {
        Testimonial::updateOrCreate(
            ['name_en' => $nameEn],
            [
                'image_path' => $imagePath,
                'name_ar' => $nameAr,
                'quote_ar' => null,
                'quote_en' => null,
                'sort_order' => $sortOrder,
                'is_published' => true,
            ]
        );
    }
}
