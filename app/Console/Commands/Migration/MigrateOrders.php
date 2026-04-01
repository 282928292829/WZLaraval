<?php

namespace App\Console\Commands\Migration;

use Database\Seeders\DeletedUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate orders from the legacy WordPress database into the Laravel schema.
 *
 * Source:  wp_posts (post_type='orders') + wp_postmeta  (legacy connection)
 * Target:  orders + order_items  (default connection)
 *
 * Order number: Use post_name as-is. On collision, append -2, -3, etc.
 *
 * Status mapping (WP 0–7 → Laravel slug):
 *   0 → pending, 1 → needs_payment, 2 → processing, 3 → purchasing,
 *   4 → shipped, 5 → delivered, 6 → cancelled, 7 → on_hold
 */
class MigrateOrders extends Command
{
    protected $signature = 'migrate:orders
                            {--chunk=200 : Number of orders to process per batch}
                            {--limit= : Only migrate this many orders (for testing)}';

    protected $description = 'Migrate orders from legacy WordPress database into orders + order_items';

    private const STATUS_MAP = [
        0 => 'pending',
        1 => 'needs_payment',
        2 => 'processing',
        3 => 'purchasing',
        4 => 'shipped',
        5 => 'delivered',
        6 => 'cancelled',
        7 => 'on_hold',
    ];

    private int $ordersInserted = 0;

    private int $itemsInserted = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('=== MigrateOrders ===');

        $userMap = DB::table('users')->pluck('id', 'email')->toArray();

        $chunkSize = (int) $this->option('chunk');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $query = DB::connection('legacy')
            ->table('posts')
            ->where('post_type', 'orders')
            ->where('post_status', 'publish')
            ->orderBy('ID');

        $total = $limit ?? $query->count();
        $this->line("Source: {$total} order posts");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $seenOrderNumbers = [];

        $query->chunk($chunkSize, function ($posts) use ($userMap, $bar, $limit, &$processed, &$seenOrderNumbers) {
            try {
                $postIds = $posts->pluck('ID')->toArray();

                $metaRows = DB::connection('legacy')
                    ->table('postmeta')
                    ->whereIn('post_id', $postIds)
                    ->get();

                $meta = [];
                foreach ($metaRows as $row) {
                    $meta[$row->post_id][$row->meta_key] = $row->meta_value;
                }

                $authorIds = $posts->pluck('post_author')->unique()->toArray();
                $authorEmails = DB::connection('legacy')
                    ->table('users')
                    ->whereIn('ID', $authorIds)
                    ->pluck('user_email', 'ID')
                    ->toArray();

                $ordersToInsert = [];
                $itemsByPostId = [];
                $queuedOrderNumbers = []; // order numbers assigned in this chunk (before batch insert)

                foreach ($posts as $post) {
                    if ($limit && $processed >= $limit) {
                        break;
                    }

                    $bar->advance();
                    $processed++;

                    if (DB::table('orders')->where('wp_post_id', $post->ID)->exists()) {
                        $this->skipped++;

                        continue;
                    }

                    $postMeta = $meta[$post->ID] ?? [];
                    $baseOrderNumber = $post->post_name ?: (string) ($postMeta['order_id'] ?? $post->ID);
                    if ($baseOrderNumber === '') {
                        $baseOrderNumber = (string) $post->ID;
                    }

                    // Duplicate order numbers: first = plain, 2nd = -2, 3rd = -3, etc.
                    // Check DB + previous chunks (seenOrderNumbers) + current chunk queue (queuedOrderNumbers)
                    $orderNumber = $baseOrderNumber;
                    $suffix = 2;
                    while (DB::table('orders')->where('order_number', $orderNumber)->exists()
                        || isset($seenOrderNumbers[$orderNumber])
                        || isset($queuedOrderNumbers[$orderNumber])) {
                        $orderNumber = $baseOrderNumber.'-'.$suffix;
                        $suffix++;
                    }
                    $queuedOrderNumbers[$orderNumber] = true;
                    $seenOrderNumbers[$orderNumber] = true;

                    $authorEmail = $authorEmails[$post->post_author] ?? null;
                    $userId = $authorEmail ? ($userMap[$authorEmail] ?? null) : null;

                    if (! $userId) {
                        static $fallbackUserId = null;
                        if ($fallbackUserId === null) {
                            $fallbackUserId = DB::table('users')->where('email', DeletedUserSeeder::EMAIL)->value('id')
                                ?? DB::table('users')->min('id');
                        }
                        $userId = $fallbackUserId;
                    }

                    $wpStatus = (int) ($postMeta['order_status'] ?? 0);
                    $laravelStatus = self::STATUS_MAP[$wpStatus] ?? 'pending';

                    $subtotal = 0;
                    for ($i = 1; $i <= 30; $i++) {
                        $price = (float) ($postMeta["p_price_{$i}"] ?? 0);
                        $qty = (int) ($postMeta["p_qty_{$i}"] ?? 1);
                        if ($price > 0) {
                            $subtotal += $price * $qty;
                        }
                    }

                    $paymentAmount = isset($postMeta['payment_amount']) ? (float) $postMeta['payment_amount'] : null;
                    $isPaid = $paymentAmount && $paymentAmount > 0;
                    $paymentDate = $this->parseDate($postMeta['payment_date'] ?? null);
                    $shippingSnapshot = $this->parseJson($postMeta['shipping_address_snapshot'] ?? null);

                    $maxAmount = 99_999_999.99;
                    $subtotal = min($subtotal, $maxAmount);
                    $paymentAmount = $paymentAmount !== null ? min($paymentAmount, $maxAmount) : null;

                    $ordersToInsert[] = [
                        'order_number' => $orderNumber,
                        'wp_post_id' => $post->ID,
                        'user_id' => $userId,
                        'status' => $laravelStatus,
                        'layout_option' => 2,
                        'notes' => $post->post_content ?: null,
                        'subtotal' => $subtotal > 0 ? $subtotal : null,
                        'total_amount' => $paymentAmount ?: ($subtotal > 0 ? $subtotal : null),
                        'payment_amount' => $paymentAmount,
                        'payment_date' => $paymentDate,
                        'payment_method' => $postMeta['payment_method'] ?? null,
                        'payment_receipt' => $postMeta['payment_receipt'] ?? null,
                        'is_paid' => $isPaid,
                        'paid_at' => $isPaid ? ($paymentDate ?? $post->post_modified) : null,
                        'tracking_number' => $postMeta['tracking_number'] ?? null,
                        'tracking_company' => $postMeta['tracking_company'] ?? null,
                        'shipping_address_snapshot' => $shippingSnapshot ? json_encode($shippingSnapshot) : null,
                        'currency' => 'SAR',
                        'created_at' => $post->post_date,
                        'updated_at' => $post->post_modified ?? $post->post_date,
                    ];

                    $items = $this->extractItems($postMeta, $post->ID);
                    if ($items) {
                        $itemsByPostId[$orderNumber] = $items;
                    }
                }

                if ($ordersToInsert) {
                    try {
                        DB::table('orders')->insert($ordersToInsert);
                        $this->ordersInserted += count($ordersToInsert);
                    } catch (\Exception $e) {
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
                            foreach ($allItems as $item) {
                                try {
                                    DB::table('order_items')->insert($item);
                                    $this->itemsInserted++;
                                } catch (\Exception) {
                                    $this->errors++;
                                }
                            }
                        }
                    }
                }

                if ($limit && $processed >= $limit) {
                    return false;
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

    private function extractItems(array $meta, int $postId): array
    {
        $items = [];

        for ($i = 1; $i <= 30; $i++) {
            $slotKey = "p_{$i}";
            $urlKey = "p_url_{$i}";
            $hasSlot = array_key_exists($slotKey, $meta) || array_key_exists($urlKey, $meta);

            if (! $hasSlot) {
                break;
            }

            $url = trim($meta[$urlKey] ?? '');
            if (mb_strlen($url) > 4096) {
                $url = mb_substr($url, 0, 4096);
            }

            if (empty($url)) {
                continue;
            }

            $isUrl = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');

            $qty = max(1, (int) ($meta["p_qty_{$i}"] ?? 1));
            $price = (float) ($meta["p_price_{$i}"] ?? 0);

            $imagePath = null;
            if (! empty($meta["p_img_{$i}"])) {
                $imagePath = $this->resolveAttachmentPath((int) $meta["p_img_{$i}"]);
            }

            $items[] = [
                'url' => $url,
                'is_url' => $isUrl,
                'source_host' => $isUrl ? order_item_source_host($url) : null,
                'qty' => $qty,
                'color' => $this->truncate($meta["p_color_{$i}"] ?? null, 500),
                'size' => $this->truncate($meta["p_size_{$i}"] ?? null, 500),
                'notes' => $this->truncate($meta["p_info_{$i}"] ?? null, 2000),
                'image_path' => $imagePath,
                'currency' => null,
                'unit_price' => $price > 0 ? $price : null,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $items;
    }

    private function resolveAttachmentPath(int $attachmentId): ?string
    {
        static $cache = [];

        if (array_key_exists($attachmentId, $cache)) {
            return $cache[$attachmentId];
        }

        $guid = DB::connection('legacy')
            ->table('posts')
            ->where('ID', $attachmentId)
            ->where('post_type', 'attachment')
            ->value('guid');

        if (! $guid) {
            return $cache[$attachmentId] = null;
        }

        if (preg_match('#/uploads/(.+)$#', $guid, $m)) {
            return $cache[$attachmentId] = 'orders/attachments/'.$m[1];
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

    private function parseDate(?string $v): ?string
    {
        if (! $v || $v === '0000-00-00' || str_starts_with((string) $v, '0000-00-00')) {
            return null;
        }

        return $v;
    }

    private function parseJson(mixed $v): mixed
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_array($v)) {
            return $v;
        }
        $d = json_decode((string) $v, true);

        return json_last_error() === JSON_ERROR_NONE ? $d : null;
    }
}
