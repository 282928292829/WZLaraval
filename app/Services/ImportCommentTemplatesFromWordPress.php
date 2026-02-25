<?php

namespace App\Services;

use App\Models\CommentTemplate;
use Illuminate\Support\Facades\DB;

class ImportCommentTemplatesFromWordPress
{
    /**
     * Import comment templates from legacy WordPress DB (wp_posts post_type=comments_template).
     * Replaces all existing templates. Maps: post_title→title, post_content→content,
     * menu_order→sort_order, usage_count meta→usage_count.
     */
    public function import(bool $replaceExisting = true): array
    {
        $legacy = DB::connection('legacy');

        if (! $legacy->getSchemaBuilder()->hasTable('wp_posts')) {
            return ['success' => false, 'message' => __('comment_templates.import_legacy_not_found'), 'count' => 0];
        }

        $posts = $legacy->table('wp_posts')
            ->where('post_type', 'comments_template')
            ->where('post_status', 'publish')
            ->orderBy('menu_order')
            ->orderBy('ID')
            ->get();

        if ($posts->isEmpty()) {
            return ['success' => true, 'message' => __('comment_templates.import_none_found'), 'count' => 0];
        }

        $postIds = $posts->pluck('ID')->all();
        $metaRows = $legacy->table('wp_postmeta')
            ->whereIn('post_id', $postIds)
            ->where('meta_key', 'usage_count')
            ->get();

        $usageByPost = $metaRows->keyBy('post_id');

        if ($replaceExisting) {
            CommentTemplate::query()->delete();
        }

        $imported = 0;
        foreach ($posts as $post) {
            $usageCount = (int) ($usageByPost->get($post->ID)?->meta_value ?? 0);
            $content = strip_tags((string) $post->post_content);
            $title = trim((string) $post->post_title) ?: 'Template '.$post->ID;

            CommentTemplate::create([
                'title' => $title,
                'content' => $content,
                'usage_count' => $usageCount,
                'sort_order' => (int) $post->menu_order,
                'is_active' => true,
            ]);
            $imported++;
        }

        return [
            'success' => true,
            'message' => __('comment_templates.import_success', ['count' => $imported]),
            'count' => $imported,
        ];
    }
}
