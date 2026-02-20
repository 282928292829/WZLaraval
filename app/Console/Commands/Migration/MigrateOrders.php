<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate orders from the legacy WordPress database into the Laravel schema.
 *
 * Source:  wp_posts (post_type='orders') + wp_postmeta  (legacy connection)
 * Target:  orders + order_items                          (default connection)
 *
 * Legacy status → Laravel status mapping:
 *   0 → pending           (calculating order value)
 *   1 → needs_payment     (value calculated, awaiting payment)
 *   2 → processing        (executing, items en route to warehouse)
 *   3 → needs_payment     (final invoice issued)
 *   4 → shipped
 *   5 → delivered
 *   6 → cancelled
 *   7 → on_hold           (awaiting customer clarification)
 *
 * Product item columns:  p_url_N, p_qty_N, p_color_N, p_size_N, p_info_N,
 *                        p_price_N, p_img_N  (N = 1…30)
 */
class MigrateOrders extends Command
{
    protected $signature = 'migrate:orders
                            {--fresh : Truncate orders + order_items before migrating}
                            {--chunk=200 : Number of orders to process per batch}
                            {--limit= : Only migrate this many orders (for testing)}';

    protected $description = 'Migrate orders from legacy WordPress database into orders + order_items';

    private const STATUS_MAP = [
        0 => 'pending',
        1 => 'needs_payment',
        2 => 'processing',
        3 => 'needs_payment',
        4 => 'shipped',
        5 => 'delivered',
        6 => 'cancelled',
        7 => 'on_hold',
    ];

    private int $ordersInserted = 0;
    private int $itemsInserted  = 0;
    private int $skipped        = 0;
    private int $errors         = 0;

    public function handle(): int
    {
        $this->info('=== MigrateOrders ===');

        if ($this->option('fresh')) {
            $this->warn('Truncating order_items, orders …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('order_comment_reads')->truncate();
            DB::table('order_comment_edits')->truncate();
            DB::table('order_comments')->truncate();
            DB::table('order_files')->truncate();
            DB::table('order_items')->truncate();
            DB::table('order_timeline')->truncate();
            DB::table('orders')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Build email → Laravel user_id map for fast lookups.
        $this->line('Building user ID map …');
        $userMap = DB::table('users')
            ->pluck('id', 'email')
            ->toArray();

        $chunkSize = (int) $this->option('chunk');
        $limit     = $this->option('limit') ? (int) $this->option('limit') : null;

        $query = DB::connection('legacy')
            ->table('wp_posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->orderBy('ID');

        $total = $limit ?? $query->count();
        $this->line("Source: {$total} order posts");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;

        $query->chunk($chunkSize, function ($posts) use ($userMap, $bar, $limit, &$processed) {
            try {
            $postIds = $posts->pluck('ID')->toArray();

            // Fetch all meta for this batch in one query.
            $metaRows = DB::connection('legacy')
                ->table('wp_postmeta')
                ->whereIn('post_id', $postIds)
                ->get();

            // Index: post_id → [meta_key => meta_value]
            // Note: p_img_N can appear multiple times (duplicate rows) — keep last value.
            $meta = [];
            foreach ($metaRows as $row) {
                $meta[$row->post_id][$row->meta_key] = $row->meta_value;
            }

            // Batch-fetch author emails for this chunk.
            $authorIds    = $posts->pluck('post_author')->unique()->toArray();
            $authorEmails = DB::connection('legacy')
                ->table('wp_users')
                ->whereIn('ID', $authorIds)
                ->pluck('user_email', 'ID')
                ->toArray(); // wp_user_id → email

            $ordersToInsert = [];
            $itemsByPostId  = [];

            foreach ($posts as $post) {
                if ($limit && $processed >= $limit) {
                    break;
                }

                $bar->advance();
                $processed++;

                $postMeta   = $meta[$post->ID] ?? [];
                $orderNumber = (string) ($postMeta['order_id'] ?? $post->ID);

                // Skip if already migrated.
                if (DB::table('orders')->where('order_number', $orderNumber)->exists()) {
                    $this->skipped++;
                    continue;
                }

                // Resolve user — fall back to system user (first admin) if not found.
                $authorEmail = $authorEmails[$post->post_author] ?? null;
                $userId      = $authorEmail ? ($userMap[$authorEmail] ?? null) : null;

                if (! $userId) {
                    // Orphaned WP user — assign to first admin in Laravel.
                    static $fallbackUserId = null;

                    if ($fallbackUserId === null) {
                        $fallbackUserId = DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('roles.name', 'admin')
                            ->value('model_has_roles.model_id')
                            ?? DB::table('users')->min('id');
                    }

                    $userId = $fallbackUserId;
                }

                $wpStatus    = (int) ($postMeta['order_status'] ?? 0);
                $laravelStatus = self::STATUS_MAP[$wpStatus] ?? 'pending';

                // Calculate subtotal from all product prices.
                $subtotal = 0;
                for ($i = 1; $i <= 30; $i++) {
                    $price = (float) ($postMeta["p_price_{$i}"] ?? 0);
                    $qty   = (int)   ($postMeta["p_qty_{$i}"]   ?? 1);

                    if ($price > 0) {
                        $subtotal += $price * $qty;
                    }
                }

                $ordersToInsert[] = [
                    'order_number' => $orderNumber,
                    'user_id'      => $userId,
                    'status'       => $laravelStatus,
                    'layout_option' => 2,
                    'subtotal'     => $subtotal > 0 ? $subtotal : null,
                    'currency'     => 'SAR',
                    'created_at'   => $post->post_date,
                    'updated_at'   => $post->post_modified ?? $post->post_date,
                ];

                // Build order items.
                $items = $this->extractItems($postMeta, $post->ID);
                if ($items) {
                    $itemsByPostId[$orderNumber] = $items;
                }
            }

            // Batch insert orders.
            if ($ordersToInsert) {
                try {
                    DB::table('orders')->insert($ordersToInsert);
                    $this->ordersInserted += count($ordersToInsert);
                } catch (\Exception $e) {
                    // Fall back to single inserts to isolate the bad row.
                    foreach ($ordersToInsert as $row) {
                        try {
                            DB::table('orders')->insert($row);
                            $this->ordersInserted++;
                        } catch (\Exception $inner) {
                            $this->errors++;
                            $this->newLine();
                            $this->error("Order #{$row['order_number']}: {$inner->getMessage()}");
                        }
                    }
                }
            }

            // Now insert order items using the newly created order IDs.
            if ($itemsByPostId) {
                $orderIds = DB::table('orders')
                    ->whereIn('order_number', array_keys($itemsByPostId))
                    ->pluck('id', 'order_number')
                    ->toArray();

                $allItems = [];

                foreach ($itemsByPostId as $orderNumber => $items) {
                    $orderId = $orderIds[$orderNumber] ?? null;

                    if (! $orderId) {
                        continue;
                    }

                    foreach ($items as $item) {
                        $item['order_id'] = $orderId;
                        $allItems[] = $item;
                    }
                }

                if ($allItems) {
                    try {
                        DB::table('order_items')->insert($allItems);
                        $this->itemsInserted += count($allItems);
                    } catch (\Exception $e) {
                        // Fall back to single item inserts.
                        foreach ($allItems as $item) {
                            try {
                                DB::table('order_items')->insert($item);
                                $this->itemsInserted++;
                            } catch (\Exception $ie) {
                                $this->errors++;
                            }
                        }
                    }
                }
            }

            if ($limit && $processed >= $limit) {
                return false; // Stop chunking.
            }
            } catch (\Exception $e) {
                $this->errors++;
                $this->newLine();
                $this->error("Chunk error: {$e->getMessage()}");
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Orders inserted : {$this->ordersInserted}");
        $this->info("Items inserted  : {$this->itemsInserted}");
        $this->line("Skipped         : {$this->skipped}");

        if ($this->errors > 0) {
            $this->error("Errors          : {$this->errors}");
        }

        return self::SUCCESS;
    }

    /**
     * Parse product item fields from post meta (p_url_N, p_qty_N, etc.)
     * and return an array of order_items rows (without order_id).
     */
    private function extractItems(array $meta, int $postId): array
    {
        $items = [];

        for ($i = 1; $i <= 30; $i++) {
            // A product slot exists when p_N key is present or p_url_N is non-empty.
            $slotKey = "p_{$i}";
            $urlKey  = "p_url_{$i}";

            $hasSlot = array_key_exists($slotKey, $meta) || array_key_exists($urlKey, $meta);

            if (! $hasSlot) {
                break; // Products are sequential — stop on first gap.
            }

            $url = trim($meta[$urlKey] ?? '');

            // Strip page content that sometimes gets appended to the URL field
            // (happens when users paste from browser address bar on old mobile WP theme).
            if (strlen($url) > 2000) {
                $url = substr($url, 0, 2000);
            }

            if (empty($url)) {
                continue; // Empty slot — skip.
            }

            $isUrl = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');

            $qty   = max(1, (int) ($meta["p_qty_{$i}"] ?? 1));
            $price = (float) ($meta["p_price_{$i}"] ?? 0);

            $imagePath = null;
            if (! empty($meta["p_img_{$i}"])) {
                // Resolve WP attachment ID to a relative file path.
                $imagePath = $this->resolveAttachmentPath((int) $meta["p_img_{$i}"]);
            }

            $items[] = [
                'url'        => $url,
                'is_url'     => $isUrl,
                'qty'        => $qty,
                'color'      => $this->truncate($meta["p_color_{$i}"] ?? null, 100),
                'size'       => $this->truncate($meta["p_size_{$i}"]  ?? null, 100),
                'notes'      => $meta["p_info_{$i}"] ?? null,
                'image_path' => $imagePath,
                'currency'   => null,
                'unit_price' => $price > 0 ? $price : null,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $items;
    }

    /**
     * Resolve a WP attachment ID to a storage-relative path.
     *
     * Uses a static cache to avoid redundant DB queries within a single run.
     */
    private function resolveAttachmentPath(int $attachmentId): ?string
    {
        static $cache = [];

        if (array_key_exists($attachmentId, $cache)) {
            return $cache[$attachmentId];
        }

        $guid = DB::connection('legacy')
            ->table('wp_posts')
            ->where('ID', $attachmentId)
            ->where('post_type', 'attachment')
            ->value('guid');

        if (! $guid) {
            return $cache[$attachmentId] = null;
        }

        if (preg_match('#/uploads/(.+)$#', $guid, $m)) {
            return $cache[$attachmentId] = 'orders/attachments/' . $m[1];
        }

        return $cache[$attachmentId] = null;
    }

    private function truncate(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }
}
