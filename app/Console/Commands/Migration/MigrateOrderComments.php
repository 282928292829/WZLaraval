<?php

namespace App\Console\Commands\Migration;

use Database\Seeders\DeletedUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate order comments from the legacy WordPress database.
 *
 * Source:  wp_comments WHERE comment_post_ID = order post  (legacy connection)
 * Target:  order_comments  (default connection)
 *
 * Orphan comments (e.g. from deleted user ulgasan581@gmail.com): Assign to
 * Deleted User placeholder, set is_system=true.
 *
 * comment_approved: '1' or '0' = migrate; 'trash'/'spam' = skip
 */
class MigrateOrderComments extends Command
{
    protected $signature = 'migrate:order-comments
                            {--chunk=1000 : Number of records to process per batch}';

    protected $description = 'Migrate order comments from legacy WordPress database into order_comments';

    private int $inserted = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('=== MigrateOrderComments ===');

        $userMap = DB::table('users')->pluck('id', 'email')->toArray();
        $deletedUserId = DB::table('users')->where('email', DeletedUserSeeder::EMAIL)->value('id');

        if (! $deletedUserId) {
            $this->error('Deleted User not found. Run MigrateAll (or seed DeletedUserSeeder) first.');

            return self::FAILURE;
        }

        $wpPostToOrderId = $this->buildWpPostToOrderIdMap();

        $total = DB::connection('legacy')
            ->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->whereIn('c.comment_approved', ['1', '0'])
            ->count();

        $this->line("Source: {$total} order comments");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('legacy')
            ->table('comments as c')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->whereIn('c.comment_approved', ['1', '0'])
            ->select('c.*')
            ->orderBy('c.comment_ID')
            ->chunk((int) $this->option('chunk'), function ($comments) use (
                $userMap,
                $wpPostToOrderId,
                $deletedUserId,
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

                    $userId = null;
                    $isSystem = false;

                    if ($comment->user_id > 0) {
                        $email = DB::connection('legacy')
                            ->table('users')
                            ->where('ID', $comment->user_id)
                            ->value('user_email');
                        $userId = $userMap[$email] ?? null;
                    }

                    if (! $userId && ! empty($comment->comment_author_email)) {
                        $userId = $userMap[$comment->comment_author_email] ?? null;
                    }

                    if (! $userId) {
                        $userId = $deletedUserId;
                        $isSystem = true;
                    }

                    $body = trim($comment->comment_content);

                    if (empty($body)) {
                        $this->skipped++;

                        continue;
                    }

                    $toInsert[] = [
                        'order_id' => $orderId,
                        'user_id' => $userId,
                        'body' => $body,
                        'is_internal' => false,
                        'is_system' => $isSystem,
                        'is_edited' => false,
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

    private function buildWpPostToOrderIdMap(): array
    {
        $map = [];
        foreach (DB::table('orders')->whereNotNull('wp_post_id')->get(['id', 'wp_post_id']) as $order) {
            $map[(int) $order->wp_post_id] = $order->id;
        }

        return $map;
    }
}
