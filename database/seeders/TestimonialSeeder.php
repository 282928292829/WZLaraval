<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TestimonialSeeder extends Seeder
{
    /**
     * Legacy testimonial images from WordPress (wasetzon.com/testimonials).
     * Copied from Wordpress/pwa3/old-wordpress/old-wp-content/uploads/2020/10/
     * Each entry: [sort_order, filename, name_ar, name_en, quote_ar, quote_en]
     */
    private array $testimonials = [
        [
            1,
            '1-862x862.png',
            null,
            null,
            'تجربة رائعة! توصيل سريع وخدمة عملاء ممتازة. أنصح الجميع باستخدام وسيط زون.',
            'Excellent experience! Fast delivery and great customer service. I recommend Wasetzon to everyone.',
        ],
        [
            2,
            '2-1-862x862.png',
            null,
            null,
            'اشتريت عدة مرات وكانت المعاملة دائماً احترافية. الشحن وصل في الوقت المحدد.',
            'I have ordered multiple times and the service has always been professional. Shipping arrived on time.',
        ],
        [3, '3-862x862.png', null, null, null, null],
        [4, '4-862x862.png', null, null, null, null],
        [5, '5-1-862x862.png', null, null, null, null],
        [6, '6-862x862.png', null, null, null, null],
        [7, '7-862x862.png', null, null, null, null],
        [8, '8-862x862.png', null, null, null, null],
        [9, '9-862x862.png', null, null, null, null],
        [10, '10-862x862.png', null, null, null, null],
        [11, '11-862x862.png', null, null, null, null],
        [12, '12-862x862.png', null, null, null, null],
    ];

    private string $legacyPath = '';

    public function __construct()
    {
        $base = dirname(__DIR__, 2); // wasetzonlaraval/
        $this->legacyPath = $base.'/../Wordpress/pwa3/old-wordpress/old-wp-content/uploads/2020/10';
    }

    public function run(): void
    {
        $disk = Storage::disk('public');
        $dir = 'testimonials';
        $disk->makeDirectory($dir);

        Testimonial::query()->delete();

        foreach ($this->testimonials as [$sortOrder, $filename, $nameAr, $nameEn, $quoteAr, $quoteEn]) {
            $path = "{$dir}/{$filename}";

            if (! $disk->exists($path)) {
                $src = "{$this->legacyPath}/{$filename}";
                if (File::exists($src)) {
                    $disk->put($path, File::get($src));
                } else {
                    $this->command?->warn("Legacy image not found: {$src}");

                    continue;
                }
            }

            $this->upsertTestimonial($path, $sortOrder, $nameAr, $nameEn, $quoteAr, $quoteEn);
        }
    }

    private function upsertTestimonial(string $imagePath, int $sortOrder, ?string $nameAr, ?string $nameEn, ?string $quoteAr, ?string $quoteEn): void
    {
        Testimonial::updateOrCreate(
            ['image_path' => $imagePath],
            [
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'quote_ar' => $quoteAr,
                'quote_en' => $quoteEn,
                'sort_order' => $sortOrder,
                'is_published' => true,
            ]
        );
    }
}
