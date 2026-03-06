<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix merge references (merged_into) from legacy WordPress.
 *
 * Source:  wp_postmeta (meta_key=merged_into)  (legacy connection)
 * Target:  orders.merged_into, merged_at  (default connection)
 *
 * Invalid merges: Skip and log.
 */
class FixMerges extends Command
{
    protected $signature = 'migrate:fix-merges';

    protected $description = 'Resolve merged_into references from legacy WordPress into orders table';

    public function handle(): int
    {
        $this->info('=== FixMerges ===');

        $wpPostToOrderId = $this->buildWpPostToOrderIdMap();

        if (empty($wpPostToOrderId)) {
            $this->warn('No orders found. Run migrate:orders first.');

            return self::SUCCESS;
        }

        $postIds = array_keys($wpPostToOrderId);
        $chunkSize = 5000;
        $updated = 0;
        $skipped = 0;
        $now = now();

        foreach (array_chunk($postIds, $chunkSize) as $chunk) {
            $mergedInto = DB::connection('legacy')
                ->table('postmeta')
                ->where('meta_key', 'merged_into')
                ->whereIn('post_id', $chunk)
                ->get();

            foreach ($mergedInto as $m) {
                $orderId = $wpPostToOrderId[(int) $m->post_id] ?? null;
                $targetPostId = (int) $m->meta_value;
                $targetOrderId = $wpPostToOrderId[$targetPostId] ?? null;

                if (! $orderId) {
                    continue;
                }

                if (! $targetOrderId) {
                    $skipped++;
                    Log::channel('single')->info("Migration fix-merges: Skip WP post {$m->post_id} — target post {$targetPostId} not found");
                    $this->line("  Skip WP post {$m->post_id}: target post {$targetPostId} not found");

                    continue;
                }

                if ($orderId === $targetOrderId) {
                    $skipped++;

                    continue;
                }

                if ($this->wouldCreateCycle($orderId, $targetOrderId)) {
                    $skipped++;
                    Log::channel('single')->info("Migration fix-merges: Skip order {$orderId} — merge into {$targetOrderId} would create cycle");
                    $this->line("  Skip order {$orderId}: merge into {$targetOrderId} would create cycle");

                    continue;
                }

                DB::table('orders')->where('id', $orderId)->update([
                    'merged_into' => $targetOrderId,
                    'merged_at' => $now,
                ]);
                $updated++;
            }
        }

        $this->info("Updated {$updated} merge references.");
        if ($skipped > 0) {
            $this->line("Skipped: {$skipped} (invalid targets or cycles).");
        }

        return self::SUCCESS;
    }

    private function wouldCreateCycle(int $orderId, int $targetOrderId): bool
    {
        $visited = [$orderId => true];
        $current = $targetOrderId;

        for ($i = 0; $i < 1000; $i++) {
            if (isset($visited[$current])) {
                return true;
            }
            $visited[$current] = true;
            $next = DB::table('orders')->where('id', $current)->value('merged_into');
            if ($next === null) {
                return false;
            }
            $current = (int) $next;
        }

        return true;
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
