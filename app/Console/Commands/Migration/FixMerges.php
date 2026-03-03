<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix merge references (merged_into, merged_at) from legacy WordPress.
 *
 * Source:  wp_postmeta (meta_key=merged_into)  (legacy connection)
 * Target:  orders.merged_into, merged_at  (default connection)
 *
 * Must run AFTER migrate:orders (needs wp_post_id → Laravel order_id map).
 *
 * Skips invalid merges (missing target, self-merge, circular refs) and logs instead of failing.
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

        $mergedInto = DB::connection('legacy')
            ->table('wp_postmeta')
            ->where('meta_key', 'merged_into')
            ->whereIn('post_id', array_keys($wpPostToOrderId))
            ->get();

        $updated = 0;
        $skippedTargetMissing = 0;
        $skippedSelfMerge = 0;
        $skippedCircular = 0;
        $now = now();

        foreach ($mergedInto as $m) {
            $orderId = $wpPostToOrderId[(int) $m->post_id] ?? null;
            $targetPostId = (int) $m->meta_value;
            $targetOrderId = $wpPostToOrderId[$targetPostId] ?? null;

            if (! $orderId) {
                continue;
            }

            if (! $targetOrderId) {
                $skippedTargetMissing++;
                $this->line("  Skip WP post {$m->post_id}: target post {$targetPostId} not found");

                continue;
            }

            if ($orderId === $targetOrderId) {
                $skippedSelfMerge++;

                continue;
            }

            if ($this->wouldCreateCycle($orderId, $targetOrderId)) {
                $skippedCircular++;
                $this->line("  Skip order {$orderId}: merge into {$targetOrderId} would create cycle");

                continue;
            }

            DB::table('orders')->where('id', $orderId)->update([
                'merged_into' => $targetOrderId,
                'merged_at' => $now,
            ]);
            $updated++;
        }

        $this->info("Updated {$updated} merge references.");
        if ($skippedTargetMissing + $skippedSelfMerge + $skippedCircular > 0) {
            $this->line("Skipped: {$skippedTargetMissing} (target missing), {$skippedSelfMerge} (self-merge), {$skippedCircular} (circular).");
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

    /**
     * Build wp_post_id → Laravel order_id from orders.wp_post_id (set during migrate:orders).
     */
    private function buildWpPostToOrderIdMap(): array
    {
        $map = [];
        foreach (DB::table('orders')->whereNotNull('wp_post_id')->get(['id', 'wp_post_id']) as $order) {
            $map[(int) $order->wp_post_id] = $order->id;
        }

        return $map;
    }
}
