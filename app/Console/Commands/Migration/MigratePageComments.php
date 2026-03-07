<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate page comments from the legacy WordPress database.
 *
 * Source:  wp_comments WHERE post_type = 'page' AND comment_approved = '1'  (legacy connection)
 * Target:  page_comments  (default connection)
 *
 * Must run AFTER migrate:pages (uses page ID map file).
 */
class MigratePageComments extends Command
{
    protected $signature = 'migrate:page-comments';

    protected $description = 'Migrate page comments from legacy WordPress database';

    private int $inserted = 0;

    private int $skipped = 0;

    public function handle(): int
    {
        $this->info('=== MigratePageComments ===');

        $mapPath = storage_path(MigratePages::MAP_PATH);

        if (! file_exists($mapPath)) {
            $this->error("Page ID map not found at {$mapPath}. Run migrate:pages first.");

            return self::FAILURE;
        }

        $wpToLaravelPageId = json_decode(file_get_contents($mapPath), true);
        $userMap = DB::table('users')->pluck('id', 'email')->toArray();

        $comments = DB::connection('legacy')
            ->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'page')
            ->where('c.comment_approved', '1')
            ->select('c.*')
            ->orderBy('c.comment_parent')
            ->orderBy('c.comment_ID')
            ->get();

        $this->line("Source: {$comments->count()} page comments");

        $idMap = [];
        $bar = $this->output->createProgressBar($comments->count());
        $bar->start();

        foreach ($comments as $comment) {
            $bar->advance();

            $pageId = $wpToLaravelPageId[$comment->comment_post_ID] ?? null;

            if (! $pageId) {
                $this->skipped++;

                continue;
            }

            $userId = null;
            if ($comment->user_id > 0) {
                $email = DB::connection('legacy')
                    ->table('users')
                    ->where('ID', $comment->user_id)
                    ->value('user_email');
                $userId = $userMap[$email] ?? null;
            }

            $parentId = null;
            if ($comment->comment_parent > 0) {
                $parentId = $idMap[$comment->comment_parent] ?? null;
            }

            $body = trim($comment->comment_content);

            if (empty($body)) {
                $this->skipped++;

                continue;
            }

            $newId = DB::table('page_comments')->insertGetId([
                'page_id' => $pageId,
                'user_id' => $userId,
                'parent_id' => $parentId,
                'guest_name' => $userId ? null : ($comment->comment_author ?: null),
                'guest_email' => $userId ? null : ($comment->comment_author_email ?: null),
                'body' => $body,
                'status' => 'approved',
                'is_edited' => false,
                'created_at' => $comment->comment_date,
                'updated_at' => $comment->comment_date,
            ]);

            $idMap[$comment->comment_ID] = $newId;
            $this->inserted++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Inserted : {$this->inserted}");
        $this->line("Skipped  : {$this->skipped}");

        return self::SUCCESS;
    }
}
