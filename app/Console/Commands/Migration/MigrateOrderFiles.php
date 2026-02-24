<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Migrate order-related files from the legacy WordPress uploads directory.
 *
 * Two file types are handled:
 *
 * 1. Product images: stored in wp_postmeta as p_img_N (WP attachment ID).
 *    The attachment GUID gives the original URL; the file lives on disk at
 *    LEGACY_UPLOADS_PATH / <relative-path-from-url>.
 *    Target: storage/app/public/orders/products/{year}/{month}/{filename}
 *    Linked to: order_files (type=product_image) + order_items.image_path
 *
 * 2. Comment attachments: stored in wp_commentmeta as attachmentId.
 *    These are receipts or arbitrary files uploaded during order comments.
 *    Target: storage/app/public/orders/receipts/{year}/{month}/{filename}
 *    Linked to: order_files (type=receipt)
 *
 * Run this AFTER migrate:orders and migrate:order-comments.
 *
 * Set LEGACY_UPLOADS_PATH in .env (or config/migration.php) to the absolute
 * path of the legacy wp-content/uploads directory:
 *   LEGACY_UPLOADS_PATH=/path/to/old-wp-content/uploads
 */
class MigrateOrderFiles extends Command
{
    protected $signature = 'migrate:order-files
                            {--fresh : Truncate order_files before migrating}
                            {--chunk=500 : Batch size}
                            {--dry-run : Scan and report without copying files or writing DB rows}';

    protected $description = 'Copy legacy WP upload files into Laravel storage and seed order_files table';

    private string $uploadsPath = '';

    private bool $dryRun = false;

    private int $copied = 0;

    private int $skipped = 0;

    private int $missing = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $this->info('=== MigrateOrderFiles ===');

        $this->uploadsPath = rtrim(config('migration.legacy_uploads_path'), '/');

        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('[DRY RUN] No files will be copied and no DB rows will be written.');
        }

        if (! is_dir($this->uploadsPath)) {
            $this->error("Uploads directory not found: {$this->uploadsPath}");
            $this->line('Set LEGACY_UPLOADS_PATH in .env to the absolute path of the legacy uploads folder.');

            return self::FAILURE;
        }

        if ($this->option('fresh') && ! $this->dryRun) {
            $this->warn('Truncating order_files …');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('order_files')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            // Also clear image_path on order_items.
            DB::table('order_items')->update(['image_path' => null]);
        }

        $this->migrateProductImages();
        $this->migrateCommentAttachments();

        $this->newLine();
        $this->info("Copied  : {$this->copied}");
        $this->line("Skipped : {$this->skipped}  (already exists or empty)");
        $this->line("Missing : {$this->missing}  (source file not found on disk)");

        if ($this->errors > 0) {
            $this->error("Errors  : {$this->errors}");
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Product images (p_img_N postmeta)
    // -------------------------------------------------------------------------

    private function migrateProductImages(): void
    {
        $this->line('');
        $this->line('--- Product images (p_img_N) ---');

        // Collect all p_img_N meta from order posts.
        $attachmentIds = DB::connection('legacy')
            ->table('wp_postmeta')
            ->join('wp_posts', 'wp_posts.ID', '=', 'wp_postmeta.post_id')
            ->where('wp_posts.post_type', 'orders')
            ->where('wp_postmeta.meta_key', 'like', 'p_img_%')
            ->whereRaw('wp_postmeta.meta_value REGEXP \'^[0-9]+$\'')
            ->select(
                'wp_postmeta.post_id',
                'wp_postmeta.meta_key',
                DB::connection('legacy')->raw('CAST(wp_postmeta.meta_value AS UNSIGNED) as attachment_id')
            )
            ->get();

        // Load order_number → Laravel order_id map.
        $orderMap = $this->buildOrderMap();

        $this->line("Found {$attachmentIds->count()} product image meta rows");

        $bar = $this->output->createProgressBar($attachmentIds->count());
        $bar->start();

        foreach ($attachmentIds as $row) {
            $bar->advance();

            // Resolve attachment.
            $attachment = DB::connection('legacy')
                ->table('wp_posts')
                ->where('ID', $row->attachment_id)
                ->where('post_type', 'attachment')
                ->first(['ID', 'guid', 'post_mime_type', 'post_parent']);

            if (! $attachment || empty($attachment->guid)) {
                $this->missing++;

                continue;
            }

            // Derive source path from GUID.
            $srcPath = $this->guidToLocalPath($attachment->guid);

            if (! $srcPath || ! file_exists($srcPath)) {
                $this->missing++;

                continue;
            }

            // Determine target path.
            $relativePath = $this->guidToRelativePath($attachment->guid);
            $destPath = 'orders/products/'.$relativePath;

            if (! $this->dryRun && Storage::disk('public')->exists($destPath)) {
                $this->skipped++;

                continue;
            }

            // Copy file.
            if (! $this->dryRun) {
                try {
                    Storage::disk('public')->put($destPath, file_get_contents($srcPath));
                } catch (\Exception $e) {
                    $this->errors++;

                    continue;
                }
            }

            // Resolve order and item.
            $orderNumber = DB::connection('legacy')
                ->table('wp_postmeta')
                ->where('post_id', $row->post_id)
                ->where('meta_key', 'order_id')
                ->value('meta_value');

            $orderId = $orderMap[(string) $orderNumber] ?? null;

            if (! $orderId) {
                $this->copied++;

                continue;
            }

            // Update order_items.image_path.
            preg_match('/p_img_(\d+)/', $row->meta_key, $m);
            $itemIndex = (int) ($m[1] ?? 0);

            if ($itemIndex > 0 && ! $this->dryRun) {
                DB::table('order_items')
                    ->where('order_id', $orderId)
                    ->where('sort_order', $itemIndex)
                    ->update(['image_path' => $destPath]);
            }

            // Resolve uploader user.
            $uploaderEmail = DB::connection('legacy')
                ->table('wp_users')
                ->join('wp_posts', 'wp_posts.post_author', '=', 'wp_users.ID')
                ->where('wp_posts.ID', $row->post_id)
                ->value('wp_users.user_email');

            $userId = DB::table('users')->where('email', $uploaderEmail)->value('id')
                ?? DB::table('users')->min('id');

            if (! $this->dryRun) {
                DB::table('order_files')->insertOrIgnore([
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'comment_id' => null,
                    'path' => $destPath,
                    'original_name' => basename($srcPath),
                    'mime_type' => $attachment->post_mime_type ?? null,
                    'size' => filesize($srcPath) ?: null,
                    'type' => 'product_image',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->copied++;
        }

        $bar->finish();
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Comment attachments (wp_commentmeta attachmentId)
    // -------------------------------------------------------------------------

    private function migrateCommentAttachments(): void
    {
        $this->line('');
        $this->line('--- Comment attachments (wp_commentmeta) ---');

        $orderMap = $this->buildOrderMap();
        $userMap = DB::table('users')->pluck('id', 'email')->toArray();
        $wpPostToOrderId = $this->buildWpPostToOrderIdMap($orderMap);
        $fallbackUserId = DB::table('users')->min('id');

        $baseQuery = DB::connection('legacy')
            ->table('wp_commentmeta as cm')
            ->join('wp_comments as c', 'c.comment_ID', '=', 'cm.comment_id')
            ->join('wp_posts as p', 'p.ID', '=', 'c.comment_post_ID')
            ->where('p.post_type', 'orders')
            ->where('cm.meta_key', 'attachmentId')
            ->whereRaw('cm.meta_value REGEXP \'^[0-9]+$\'');

        $total = (clone $baseQuery)->count();
        $this->line("Found {$total} comment attachment meta rows");

        if ($total === 0) {
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $chunkSize = (int) $this->option('chunk');
        $bar->start();

        $baseQuery
            ->select(
                'cm.meta_id',
                'cm.meta_value as attachment_id',
                'c.comment_post_ID',
                'c.comment_author_email',
                'c.comment_date'
            )
            ->orderBy('cm.meta_id')
            ->chunk($chunkSize, function ($rows) use ($wpPostToOrderId, $userMap, $fallbackUserId, $bar) {
                foreach ($rows as $row) {
                    $bar->advance();

                    try {
                        $attachment = DB::connection('legacy')
                            ->table('wp_posts')
                            ->where('ID', (int) $row->attachment_id)
                            ->where('post_type', 'attachment')
                            ->first(['ID', 'guid', 'post_mime_type']);

                        if (! $attachment || empty($attachment->guid)) {
                            $this->missing++;

                            continue;
                        }

                        $srcPath = $this->guidToLocalPath($attachment->guid);

                        if (! $srcPath || ! file_exists($srcPath)) {
                            $this->missing++;

                            continue;
                        }

                        $relativePath = $this->guidToRelativePath($attachment->guid);
                        $destPath = 'orders/receipts/'.$relativePath;

                        if (! $this->dryRun && Storage::disk('public')->exists($destPath)) {
                            $this->skipped++;

                            continue;
                        }

                        if (! $this->dryRun) {
                            Storage::disk('public')->put($destPath, file_get_contents($srcPath));
                        }

                        $orderId = $wpPostToOrderId[(int) $row->comment_post_ID] ?? null;

                        if (! $orderId) {
                            $this->copied++;

                            continue;
                        }

                        $uploaderEmail = ! empty($row->comment_author_email) ? $row->comment_author_email : null;
                        $userId = ($uploaderEmail ? ($userMap[$uploaderEmail] ?? null) : null) ?? $fallbackUserId;

                        if (! $this->dryRun) {
                            DB::table('order_files')->insertOrIgnore([
                                'order_id' => $orderId,
                                'user_id' => $userId,
                                'comment_id' => null,
                                'path' => $destPath,
                                'original_name' => basename($srcPath),
                                'mime_type' => $attachment->post_mime_type ?? null,
                                'size' => filesize($srcPath) ?: null,
                                'type' => 'receipt',
                                'created_at' => $row->comment_date ?? now(),
                                'updated_at' => $row->comment_date ?? now(),
                            ]);
                        }

                        $this->copied++;
                    } catch (\Exception $e) {
                        $this->errors++;
                    }
                }
            });

        $bar->finish();
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Convert a WP attachment GUID (full URL) to an absolute local file path.
     */
    private function guidToLocalPath(string $guid): ?string
    {
        if (preg_match('#/uploads/(.+)$#', $guid, $m)) {
            return $this->uploadsPath.'/'.$m[1];
        }

        return null;
    }

    /**
     * Convert a WP attachment GUID to a relative path (after /uploads/).
     */
    private function guidToRelativePath(string $guid): string
    {
        if (preg_match('#/uploads/(.+)$#', $guid, $m)) {
            return $m[1];
        }

        return basename($guid);
    }

    private function buildOrderMap(): array
    {
        return DB::table('orders')->pluck('id', 'order_number')->toArray();
    }

    private function buildWpPostToOrderIdMap(array $orderMap): array
    {
        $map = [];

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

        return $map;
    }
}
