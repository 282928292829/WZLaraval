<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate static pages from the legacy WordPress database.
 *
 * Source:  wp_posts (post_type='page')  (legacy connection)
 * Target:  pages  (default connection)
 *
 * Skip slugs: new-order, orders, new-order-2, singleorders2 (replaced by Laravel routes).
 */
class MigratePages extends Command
{
    protected $signature = 'migrate:pages';

    protected $description = 'Migrate static pages from legacy WordPress database into pages table';

    private const SKIP_SLUGS = [
        'new-order',
        'orders',
        'new-order-2',
        'singleorders2',
    ];

    private int $inserted = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('=== MigratePages ===');

        $pages = DB::connection('legacy')
            ->table('posts')
            ->where('post_type', 'page')
            ->whereIn('post_status', ['publish', 'private'])
            ->orderBy('menu_order')
            ->orderBy('ID')
            ->get();

        $this->line("Source: {$pages->count()} pages");

        $bar = $this->output->createProgressBar($pages->count());
        $bar->start();

        foreach ($pages as $page) {
            $bar->advance();

            $slug = urldecode($page->post_name);

            if (in_array($slug, self::SKIP_SLUGS, true)) {
                $this->skipped++;

                continue;
            }

            $slug = $this->uniqueSlug($slug);

            if (DB::table('pages')->where('slug', $slug)->exists()) {
                $this->skipped++;

                continue;
            }

            $meta = DB::connection('legacy')
                ->table('postmeta')
                ->where('post_id', $page->ID)
                ->whereIn('meta_key', ['_yoast_wpseo_title', '_yoast_wpseo_metadesc'])
                ->pluck('meta_value', 'meta_key')
                ->toArray();

            DB::table('pages')->insert([
                'title_ar' => $page->post_title,
                'title_en' => $page->post_title,
                'slug' => $slug,
                'body_ar' => $page->post_content ?: null,
                'body_en' => null,
                'seo_title_ar' => $meta['_yoast_wpseo_title'] ?? null,
                'seo_title_en' => null,
                'seo_description_ar' => $meta['_yoast_wpseo_metadesc'] ?? null,
                'seo_description_en' => null,
                'is_published' => $page->post_status === 'publish',
                'show_in_header' => false,
                'show_in_footer' => false,
                'menu_order' => (int) $page->menu_order,
                'created_at' => $page->post_date,
                'updated_at' => $page->post_modified ?? $page->post_date,
            ]);

            $this->inserted++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Inserted : {$this->inserted}");
        $this->line("Skipped  : {$this->skipped}  (functional WP pages or duplicates)");

        return self::SUCCESS;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base ?: \Illuminate\Support\Str::random(8);
        $counter = 0;

        while (DB::table('pages')->where('slug', $slug)->exists()) {
            $counter++;
            $slug = $base."-{$counter}";
        }

        return $slug;
    }
}
