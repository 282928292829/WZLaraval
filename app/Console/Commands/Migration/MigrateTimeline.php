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
 * Supports both formats:
 *   - {type, body, date, user?}
 *   - {action, details, timestamp}
 */
class MigrateTimeline extends Command
{
    protected $signature = 'migrate:timeline';

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

        $postIds = array_keys($wpPostToOrderId);
        $total = 0;

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $total += DB::connection('legacy')
                ->table('postmeta')
                ->where('meta_key', 'activity_log')
                ->whereIn('post_id', $chunk)
                ->count();
        }

        $bar = $this->output->createProgressBar($total ?: 1);
        $bar->start();

        foreach (array_chunk($postIds, 1000) as $chunk) {
            $meta = DB::connection('legacy')
                ->table('postmeta')
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

                    $record = $this->normalizeEntry($entry);

                    DB::table('order_timeline')->insert([
                        'order_id' => $orderId,
                        'user_id' => $record['user_id'],
                        'type' => $record['type'],
                        'status_from' => $record['status_from'],
                        'status_to' => $record['status_to'],
                        'body' => $record['body'],
                        'created_at' => $record['created_at'],
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

    /**
     * Normalize entry from either {type, body, date, user?} or {action, details, timestamp}.
     *
     * @return array{user_id: ?int, type: string, status_from: ?string, status_to: ?string, body: ?string, created_at: string}
     */
    private function normalizeEntry(array $entry): array
    {
        $createdAt = $entry['date'] ?? $entry['timestamp'] ?? now();

        if (isset($entry['type'], $entry['body'])) {
            $type = $this->mapActivityType($entry['type']);
            $body = is_array($entry['body']) ? json_encode($entry['body']) : (string) $entry['body'];
            $userId = isset($entry['user']) ? (int) $entry['user'] : null;

            return [
                'user_id' => $userId ?: null,
                'type' => $type,
                'status_from' => null,
                'status_to' => null,
                'body' => $body ?: null,
                'created_at' => $createdAt,
            ];
        }

        $action = $entry['action'] ?? '';
        $type = $this->mapActivityType($action);
        $body = $entry['details'] ?? $entry['body'] ?? $action;
        if (is_array($body)) {
            $body = json_encode($body);
        }

        return [
            'user_id' => null,
            'type' => $type,
            'status_from' => null,
            'status_to' => null,
            'body' => $body ?: null,
            'created_at' => $createdAt,
        ];
    }

    private function mapActivityType(string $action): string
    {
        $lower = strtolower($action);

        if (str_contains($lower, 'status')) {
            return 'status_change';
        }
        if (str_contains($lower, 'comment')) {
            return 'comment';
        }
        if (str_contains($lower, 'file')) {
            return 'file_upload';
        }
        if (str_contains($lower, 'payment')) {
            return 'payment';
        }
        if (str_contains($lower, 'merge')) {
            return 'merge';
        }

        return 'note';
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
