<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate order timeline (activity_log) from legacy WordPress.
 *
 * Source:  wp_postmeta (meta_key=activity_log)  (legacy connection)
 * Target:  order_timeline  (default connection)
 *
 * Must run AFTER migrate:orders (needs wp_post_id → Laravel order_id map).
 */
class MigrateTimeline extends Command
{
    protected $signature = 'migrate:timeline
                            {--fresh : Truncate order_timeline before migrating}';

    protected $description = 'Migrate order timeline (activity_log) from legacy WordPress into order_timeline';

    private int $inserted = 0;

    public function handle(): int
    {
        $this->info('=== MigrateTimeline ===');

        $wpPostToOrderId = $this->buildWpPostToOrderIdMap();

        if (empty($wpPostToOrderId)) {
            $this->warn('No orders found. Run migrate:orders first.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $this->warn('Truncating order_timeline …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('order_timeline')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $postIds = array_keys($wpPostToOrderId);
        $total = 0;

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $total += DB::connection('legacy')
                ->table('wp_postmeta')
                ->where('meta_key', 'activity_log')
                ->whereIn('post_id', $chunk)
                ->count();
        }

        $bar = $this->output->createProgressBar($total ?: 1);
        $bar->start();

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $meta = DB::connection('legacy')
                ->table('wp_postmeta')
                ->where('meta_key', 'activity_log')
                ->whereIn('post_id', $chunk)
                ->get();

            foreach ($meta as $m) {
                $orderId = $wpPostToOrderId[(int) $m->post_id] ?? null;
                if (! $orderId) {
                    $bar->advance();

                    continue;
                }

                $log = json_decode($m->meta_value, true);
                if (! is_array($log)) {
                    $log = @unserialize($m->meta_value) ?: [];
                }

                foreach ($log as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $type = $this->mapActivityType($entry['action'] ?? '');
                    $body = $entry['details'] ?? $entry['action'] ?? null;
                    if (is_array($body)) {
                        $body = json_encode($body);
                    }

                    DB::table('order_timeline')->insert([
                        'order_id' => $orderId,
                        'user_id' => null,
                        'type' => $type,
                        'status_from' => null,
                        'status_to' => null,
                        'body' => $body,
                        'created_at' => $entry['timestamp'] ?? now(),
                    ]);
                    $this->inserted++;
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Timeline entries: {$this->inserted}");

        return self::SUCCESS;
    }

    private function mapActivityType(string $action): string
    {
        return match (true) {
            str_contains($action, 'status') => 'status_change',
            str_contains($action, 'comment') => 'comment',
            str_contains($action, 'file') => 'file_upload',
            str_contains($action, 'payment') => 'payment',
            str_contains($action, 'merge') => 'merge',
            default => 'note',
        };
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
