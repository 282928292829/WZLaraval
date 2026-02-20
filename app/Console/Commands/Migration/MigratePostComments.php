<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\MigratePosts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate blog post comments from the legacy WordPress database.
 *
 * Source:  wp_comments WHERE post_type = 'post' AND comment_approved = '1'
 * Target:  post_comments
 *
 * Must run AFTER migrate:posts (to have post IDs available).
 *
 * WP comment threading (comment_parent > 0) is preserved as one level of nesting.
 * Parent comments are inserted first; replies are inserted in a second pass so that
 * parent_id can be resolved correctly.
 */
class MigratePostComments extends Command
{
    protected $signature = 'migrate:post-comments
                            {--fresh : Truncate post_comments before migrating}';

    protected $description = 'Migrate blog post comments from legacy WordPress database';

    private int $inserted = 0;
    private int $skipped  = 0;

    public function handle(): int
    {
        $this->info('=== MigratePostComments ===');

        if ($this->option('fresh')) {
            $this->warn('Truncating post_comments …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('post_comments')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Load WP post ID → Laravel post ID map written by migrate:posts.
        $mapPath = storage_path(MigratePosts::MAP_PATH);

        if (! file_exists($mapPath)) {
            $this->error("Post ID map not found at {$mapPath}. Run migrate:posts first.");
            return self::FAILURE;
        }

        $wpToLaravelPostId = json_decode(file_get_contents($mapPath), true);

        $userMap = DB::table('users')->pluck('id', 'email')->toArray();

        // Fetch all approved comments on blog posts.
        $comments = DB::connection('legacy')
            ->table('wp_comments as c')
            ->join('wp_posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'post')
            ->where('c.comment_approved', '1')
            ->select('c.*')
            ->orderBy('c.comment_parent')  // parents first (0 = top-level)
            ->orderBy('c.comment_ID')
            ->get();

        $this->line("Source: {$comments->count()} blog post comments");

        // wp_comment_ID → Laravel post_comment_id (for parent resolution).
        $idMap = [];

        $bar = $this->output->createProgressBar($comments->count());
        $bar->start();

        foreach ($comments as $comment) {
            $bar->advance();

            $postId = $wpToLaravelPostId[$comment->comment_post_ID] ?? null;

            if (! $postId) {
                $this->skipped++;
                continue;
            }

            // Resolve registered user.
            $userId = null;
            if ($comment->user_id > 0) {
                $email  = DB::connection('legacy')
                    ->table('wp_users')
                    ->where('ID', $comment->user_id)
                    ->value('user_email');
                $userId = $userMap[$email] ?? null;
            }

            // Resolve parent.
            $parentId = null;
            if ($comment->comment_parent > 0) {
                $parentId = $idMap[$comment->comment_parent] ?? null;
            }

            $body = trim($comment->comment_content);

            if (empty($body)) {
                $this->skipped++;
                continue;
            }

            $newId = DB::table('post_comments')->insertGetId([
                'post_id'     => $postId,
                'user_id'     => $userId,
                'parent_id'   => $parentId,
                'guest_name'  => $userId ? null : ($comment->comment_author ?: null),
                'guest_email' => $userId ? null : ($comment->comment_author_email ?: null),
                'body'        => $body,
                'status'      => 'approved',
                'is_edited'   => false,
                'created_at'  => $comment->comment_date,
                'updated_at'  => $comment->comment_date,
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
