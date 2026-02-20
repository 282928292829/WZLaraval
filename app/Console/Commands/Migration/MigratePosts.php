<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migrate blog posts from the legacy WordPress database.
 *
 * Source:  wp_posts (post_type='post', post_status='publish') + wp_postmeta + wp_term_relationships
 * Target:  post_categories + posts
 *
 * Legacy posts are Arabic-only: body_ar = post_content, body_en = null.
 * The "غير مصنف" (Uncategorized) category maps to a default "Uncategorized" category.
 */
class MigratePosts extends Command
{
    protected $signature = 'migrate:posts
                            {--fresh : Truncate posts + post_categories before migrating}';

    protected $description = 'Migrate blog posts and categories from legacy WordPress database';

    public const MAP_PATH = 'migration_wp_post_id_map.json';

    private int $categoriesInserted = 0;
    private int $postsInserted      = 0;
    private int $skipped            = 0;

    /** WP post ID → Laravel post ID, written for MigratePostComments to use. */
    private array $postIdMap = [];

    public function handle(): int
    {
        $this->info('=== MigratePosts ===');

        if ($this->option('fresh')) {
            $this->warn('Truncating post_comments, posts, post_categories …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('post_comments')->truncate();
            DB::table('posts')->truncate();
            DB::table('post_categories')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->migrateCategories();
        $this->migratePosts();

        // Write WP post ID → Laravel post ID map for MigratePostComments.
        $mapPath = storage_path(self::MAP_PATH);
        file_put_contents($mapPath, json_encode($this->postIdMap));
        $this->line("Post ID map written to: {$mapPath}");

        $this->info("Categories inserted : {$this->categoriesInserted}");
        $this->info("Posts inserted      : {$this->postsInserted}");
        $this->line("Skipped             : {$this->skipped}");

        return self::SUCCESS;
    }

    private function migrateCategories(): void
    {
        $this->line('Migrating categories …');

        $categories = DB::connection('legacy')
            ->table('wp_terms as t')
            ->join('wp_term_taxonomy as tt', 'tt.term_id', '=', 't.term_id')
            ->where('tt.taxonomy', 'category')
            ->select('t.term_id', 't.name', 't.slug', 'tt.description', 'tt.parent')
            ->get();

        foreach ($categories as $cat) {
            $slug = $this->uniqueSlug('post_categories', $cat->slug ?: Str::slug($cat->name));

            if (DB::table('post_categories')->where('slug', $slug)->exists()) {
                $this->skipped++;
                continue;
            }

            DB::table('post_categories')->insert([
                'name_ar'        => $cat->name,
                'name_en'        => $cat->name,
                'slug'           => $slug,
                'description_ar' => $cat->description ?: null,
                'description_en' => null,
                'sort_order'     => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $this->categoriesInserted++;
        }
    }

    private function migratePosts(): void
    {
        $this->line('Migrating posts …');

        $posts = DB::connection('legacy')
            ->table('wp_posts')
            ->where('post_type', 'post')
            ->whereIn('post_status', ['publish', 'draft'])
            ->orderBy('ID')
            ->get();

        // Build slug → category_id map for WP term lookup.
        $categoryMap = DB::table('post_categories')->pluck('id', 'slug')->toArray();

        // Build author email → user_id map.
        $userMap = DB::table('users')->pluck('id', 'email')->toArray();

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        foreach ($posts as $post) {
            $bar->advance();

            $slug = $this->uniqueSlug('posts', $this->decodeSlug($post->post_name) ?: Str::slug($post->post_title));

            if (DB::table('posts')->where('slug', $slug)->exists()) {
                $this->skipped++;
                continue;
            }

            // Fetch post meta.
            $meta = DB::connection('legacy')
                ->table('wp_postmeta')
                ->where('post_id', $post->ID)
                ->whereIn('meta_key', ['_thumbnail_id', '_yoast_wpseo_title', '_yoast_wpseo_metadesc'])
                ->pluck('meta_value', 'meta_key')
                ->toArray();

            // Resolve featured image.
            $featuredImage = null;
            if (! empty($meta['_thumbnail_id'])) {
                $guid = DB::connection('legacy')
                    ->table('wp_posts')
                    ->where('ID', (int) $meta['_thumbnail_id'])
                    ->value('guid');

                if ($guid && preg_match('#/uploads/(.+)$#', $guid, $m)) {
                    $featuredImage = 'posts/' . $m[1];
                }
            }

            // Resolve category.
            $categoryId = $this->resolvePostCategory($post->ID, $categoryMap);

            // Resolve author.
            $authorEmail = DB::connection('legacy')
                ->table('wp_users')
                ->where('ID', $post->post_author)
                ->value('user_email');
            $authorId = $userMap[$authorEmail] ?? null;

            $status      = $post->post_status === 'publish' ? 'published' : 'draft';
            $publishedAt = $status === 'published' ? $post->post_date : null;

            $newId = DB::table('posts')->insertGetId([
                'user_id'           => $authorId,
                'post_category_id'  => $categoryId,
                'title_ar'          => $post->post_title,
                'title_en'          => $post->post_title,
                'slug'              => $slug,
                'excerpt_ar'        => $post->post_excerpt ?: null,
                'excerpt_en'        => null,
                'body_ar'           => $post->post_content ?: null,
                'body_en'           => null,
                'featured_image'    => $featuredImage,
                'seo_title_ar'      => $meta['_yoast_wpseo_title'] ?? null,
                'seo_title_en'      => null,
                'seo_description_ar' => $meta['_yoast_wpseo_metadesc'] ?? null,
                'seo_description_en' => null,
                'status'            => $status,
                'published_at'      => $publishedAt,
                'created_at'        => $post->post_date,
                'updated_at'        => $post->post_modified ?? $post->post_date,
            ]);

            $this->postIdMap[$post->ID] = $newId;
            $this->postsInserted++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function resolvePostCategory(int $postId, array $categoryMap): ?int
    {
        $termSlug = DB::connection('legacy')
            ->table('wp_term_relationships as tr')
            ->join('wp_term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->join('wp_terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'category')
            ->value('t.slug');

        return $termSlug ? ($categoryMap[$termSlug] ?? null) : null;
    }

    private function decodeSlug(string $slug): string
    {
        return urldecode($slug);
    }

    private function uniqueSlug(string $table, string $base): string
    {
        $slug    = Str::slug($base) ?: Str::slug(Str::random(8));
        $counter = 0;

        while (DB::table($table)->where('slug', $slug)->exists()) {
            $counter++;
            $slug = Str::slug($base) . "-{$counter}";
        }

        return $slug;
    }
}
