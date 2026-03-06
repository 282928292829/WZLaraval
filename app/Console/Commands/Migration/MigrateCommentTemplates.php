<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate comment templates from legacy WordPress (post_type=comments_template).
 *
 * Source:  wp_posts + wp_postmeta  (legacy connection)
 * Target:  comment_templates      (default connection)
 *
 * Postmeta: usage_count, template content
 */
class MigrateCommentTemplates extends Command
{
    protected $signature = 'migrate:comment-templates';

    protected $description = 'Migrate comment templates from legacy WordPress into comment_templates';

    public function handle(): int
    {
        $this->info('=== MigrateCommentTemplates ===');

        $posts = DB::connection('legacy')
            ->table('posts')
            ->where('post_type', 'comments_template')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get();

        if ($posts->isEmpty()) {
            $this->warn('No comments_template posts found in legacy DB.');

            return self::SUCCESS;
        }

        $postIds = $posts->pluck('ID')->all();
        $metaRows = DB::connection('legacy')
            ->table('postmeta')
            ->whereIn('post_id', $postIds)
            ->whereIn('meta_key', ['usage_count'])
            ->get()
            ->keyBy('post_id');

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $inserted = 0;
        foreach ($posts as $post) {
            $usageCount = (int) ($metaRows->get($post->ID)?->meta_value ?? 0);

            if (DB::table('comment_templates')->where('title', $post->post_title ?: 'Template '.$post->ID)->exists()) {
                continue;
            }

            DB::table('comment_templates')->insert([
                'title' => $post->post_title ?: 'Template '.$post->ID,
                'content' => $post->post_content ?: '',
                'usage_count' => $usageCount,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inserted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Comment templates: {$inserted}");

        return self::SUCCESS;
    }
}
