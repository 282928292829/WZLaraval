<?php

namespace App\Console\Commands\Migration;

use App\Services\ImportCommentTemplatesFromWordPress;
use Illuminate\Console\Command;

/**
 * Migrate comment templates from legacy WordPress (post_type=comments_template).
 *
 * Source:  wp_posts + wp_postmeta  (legacy connection)
 * Target:  comment_templates      (default connection)
 */
class MigrateCommentTemplates extends Command
{
    protected $signature = 'migrate:comment-templates
                            {--fresh : Replace all existing templates (default behavior)}';

    protected $description = 'Migrate comment templates from legacy WordPress into comment_templates';

    public function handle(): int
    {
        $this->info('=== MigrateCommentTemplates ===');

        $result = app(ImportCommentTemplatesFromWordPress::class)->import(replaceExisting: true);

        if (! $result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
