<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate order comments from the legacy WordPress database.
 *
 * Source:  wp_comments WHERE post_type = 'orders'  (legacy connection)
 * Target:  order_comments                           (default connection)
 *
 * Must run AFTER migrate:users and migrate:orders.
 *
 * Trashed comments (comment_approved = 'trash') are skipped.
 * Comments from unknown users are attributed to the first admin account.
 */
class MigrateOrderComments extends Command
{
    protected $signature = 'migrate:order-comments
                            {--fresh : Truncate order_comments before migrating}
                            {--chunk=1000 : Number of records to process per batch}';

    protected $description = 'Migrate order comments from legacy WordPress database into order_comments';

    private int $inserted = 0;
    private int $skipped  = 0;
    private int $errors   = 0;

    public function handle(): int
    {
        $this->info('=== MigrateOrderComments ===');

        if ($this->option('fresh')) {
            $this->warn('Truncating order_comments …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('order_comment_reads')->truncate();
            DB::table('order_comment_edits')->truncate();
            DB::table('order_comments')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Build lookup maps.
        $this->line('Building lookup maps …');

        $userMap = DB::table('users')->pluck('id', 'email')->toArray();

        $orderMap = DB::table('orders')->pluck('id', 'order_number')->toArray();

        $fallbackUserId = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'admin')
            ->value('model_has_roles.model_id')
            ?? DB::table('users')->min('id');

        // Build WP post_id → Laravel order_id map via order_number.
        // order_number = postmeta[order_id] for the WP post.
        $this->line('Mapping WP post IDs to Laravel order IDs …');
        $wpPostToOrderId = $this->buildWpPostToOrderIdMap($orderMap);

        $total = DB::connection('legacy')
            ->table('wp_comments as c')
            ->join('wp_posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->whereIn('c.comment_approved', ['1', '0'])  // include pending (0), exclude trash/spam
            ->count();

        $this->line("Source: {$total} order comments");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('legacy')
            ->table('wp_comments as c')
            ->join('wp_posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->whereIn('c.comment_approved', ['1', '0'])
            ->select('c.*')
            ->orderBy('c.comment_ID')
            ->chunk((int) $this->option('chunk'), function ($comments) use (
                $userMap,
                $wpPostToOrderId,
                $fallbackUserId,
                $bar
            ) {
                $toInsert = [];

                foreach ($comments as $comment) {
                    $bar->advance();

                    $orderId = $wpPostToOrderId[$comment->comment_post_ID] ?? null;

                    if (! $orderId) {
                        $this->skipped++;
                        continue;
                    }

                    // Resolve author to a Laravel user ID.
                    $userId = null;

                    if ($comment->user_id > 0) {
                        $email  = DB::connection('legacy')
                            ->table('wp_users')
                            ->where('ID', $comment->user_id)
                            ->value('user_email');
                        $userId = $userMap[$email] ?? null;
                    }

                    if (! $userId) {
                        // Try matching by author email.
                        $userId = $userMap[$comment->comment_author_email] ?? null;
                    }

                    if (! $userId) {
                        $userId = $fallbackUserId;
                    }

                    $body = trim($comment->comment_content);

                    if (empty($body)) {
                        $this->skipped++;
                        continue;
                    }

                    $toInsert[] = [
                        'order_id'   => $orderId,
                        'user_id'    => $userId,
                        'body'       => $body,
                        'is_internal' => false,
                        'is_edited'  => false,
                        'created_at' => $comment->comment_date,
                        'updated_at' => $comment->comment_date,
                    ];
                }

                if ($toInsert) {
                    try {
                        DB::table('order_comments')->insert($toInsert);
                        $this->inserted += count($toInsert);
                    } catch (\Exception $e) {
                        foreach ($toInsert as $row) {
                            try {
                                DB::table('order_comments')->insert($row);
                                $this->inserted++;
                            } catch (\Exception $inner) {
                                $this->errors++;
                                $this->newLine();
                                $this->error("Comment: {$inner->getMessage()}");
                            }
                        }
                    }
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Inserted : {$this->inserted}");
        $this->line("Skipped  : {$this->skipped}");

        if ($this->errors > 0) {
            $this->error("Errors   : {$this->errors}");
        }

        return self::SUCCESS;
    }

    /**
     * Build a map of WP post ID → Laravel order ID.
     *
     * Reads order_id from wp_postmeta and looks it up in the Laravel orders table.
     * For posts without order_id meta, falls back to using WP post ID as order_number.
     */
    private function buildWpPostToOrderIdMap(array $orderMap): array
    {
        $map = [];

        // Primary pass: use order_id meta value as the lookup key.
        DB::connection('legacy')
            ->table('wp_postmeta as pm')
            ->join('wp_posts as p', 'p.ID', '=', 'pm.post_id')
            ->where('p.post_type', 'orders')
            ->where('pm.meta_key', 'order_id')
            ->select('pm.post_id', 'pm.meta_value as order_number')
            ->orderBy('pm.post_id')
            ->chunk(2000, function ($rows) use (&$map, $orderMap) {
                foreach ($rows as $row) {
                    $laravelOrderId = $orderMap[(string) $row->order_number] ?? null;
                    if ($laravelOrderId) {
                        $map[(int) $row->post_id] = $laravelOrderId;
                    }
                }
            });

        // Fallback pass (in chunks): for WP posts with no order_id meta, use post.ID as order_number.
        DB::connection('legacy')
            ->table('wp_posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->chunk(2000, function ($posts) use (&$map, $orderMap) {
                foreach ($posts as $post) {
                    $postId = (int) $post->ID;
                    if (! isset($map[$postId])) {
                        $laravelOrderId = $orderMap[(string) $postId] ?? null;
                        if ($laravelOrderId) {
                            $map[$postId] = $laravelOrderId;
                        }
                    }
                }
            });

        return $map;
    }
}
