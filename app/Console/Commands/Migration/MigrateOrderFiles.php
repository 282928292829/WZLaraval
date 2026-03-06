<?php

namespace App\Console\Commands\Migration;

use Database\Seeders\DeletedUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Migrate order-related files from the legacy WordPress uploads directory.
 *
 * 1. Product images: p_img_N → WP attachment → copy to storage
 * 2. Comment attachments: wp_commentmeta attachmentId
 *
 * Option A: When source file is missing (e.g. media not exported), still create
 * order_files and order_items.image_path with the canonical path. When media
 * is added later to the expected location, it will work without re-migration.
 *
 * Uses config('migration.legacy_uploads_path') for file paths when present.
 */
class MigrateOrderFiles extends Command
{
    protected $signature = 'migrate:order-files
                            {--chunk=500 : Batch size}
                            {--dry-run : Scan and report without copying files or writing DB rows}';

    protected $description = 'Copy legacy WP upload files into Laravel storage and seed order_files table';

    private string $uploadsPath = '';

    private bool $dryRun = false;

    private int $copied = 0;

    private int $pathsPreserved = 0;

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
            $this->warn("Uploads directory not found: {$this->uploadsPath}");
            $this->line('Preserving paths only (no file copy). Set LEGACY_UPLOADS_PATH when media is available.');
        }

        $this->migrateProductImages();
        $this->migrateCommentAttachments();

        $this->newLine();
        $this->info("Copied  : {$this->copied}");
        $this->line("Paths preserved : {$this->pathsPreserved}  (DB records created, file not on disk)");
        $this->line("Skipped : {$this->skipped}  (already exists or empty)");
        $this->line("Missing : {$this->missing}  (no attachment GUID in legacy DB)");

        if ($this->errors > 0) {
            $this->error("Errors  : {$this->errors}");
        }

        return self::SUCCESS;
    }

    private function migrateProductImages(): void
    {
        $this->line('');
        $this->line('--- Product images (p_img_N) ---');

        $attachmentIds = DB::connection('legacy')
            ->table('postmeta')
            ->join('posts', 'posts.ID', '=', 'postmeta.post_id')
            ->where('posts.post_type', 'orders')
            ->where('postmeta.meta_key', 'like', 'p_img_%')
            ->whereRaw('postmeta.meta_value REGEXP \'^[0-9]+$\'')
            ->select(
                'postmeta.post_id',
                'postmeta.meta_key',
                DB::connection('legacy')->raw('CAST(postmeta.meta_value AS UNSIGNED) as attachment_id')
            )
            ->get();

        $wpPostToOrderId = $this->buildWpPostToOrderIdMap();

        $this->line("Found {$attachmentIds->count()} product image meta rows");

        $bar = $this->output->createProgressBar($attachmentIds->count());
        $bar->start();

        foreach ($attachmentIds as $row) {
            $bar->advance();

            $attachment = DB::connection('legacy')
                ->table('posts')
                ->where('ID', $row->attachment_id)
                ->where('post_type', 'attachment')
                ->first(['ID', 'guid', 'post_mime_type', 'post_parent']);

            if (! $attachment || empty($attachment->guid)) {
                $this->missing++;

                continue;
            }

            $relativePath = $this->guidToRelativePath($attachment->guid);
            $destPath = 'orders/products/'.$relativePath;
            $srcPath = $this->guidToLocalPath($attachment->guid);
            $fileExists = $srcPath && is_file($srcPath);

            if ($fileExists && ! $this->dryRun && Storage::disk('public')->exists($destPath)) {
                $this->skipped++;

                continue;
            }

            if ($fileExists && ! $this->dryRun) {
                try {
                    Storage::disk('public')->put($destPath, file_get_contents($srcPath));
                } catch (\Exception $e) {
                    $this->errors++;

                    continue;
                }
            }

            $orderId = $wpPostToOrderId[(int) $row->post_id] ?? null;

            if (! $orderId) {
                $fileExists ? $this->copied++ : $this->pathsPreserved++;

                continue;
            }

            preg_match('/p_img_(\d+)/', $row->meta_key, $m);
            $itemIndex = (int) ($m[1] ?? 0);
            $orderItemId = null;

            if ($itemIndex > 0 && ! $this->dryRun) {
                $orderItem = DB::table('order_items')
                    ->where('order_id', $orderId)
                    ->where('sort_order', $itemIndex)
                    ->first();

                if ($orderItem) {
                    $orderItemId = $orderItem->id;
                    DB::table('order_items')
                        ->where('id', $orderItemId)
                        ->update(['image_path' => $destPath]);
                }
            }

            $uploaderEmail = DB::connection('legacy')
                ->table('users')
                ->join('posts', 'posts.post_author', '=', 'users.ID')
                ->where('posts.ID', $row->post_id)
                ->value('users.user_email');

            $userId = DB::table('users')->where('email', $uploaderEmail)->value('id')
                ?? DB::table('users')->where('email', DeletedUserSeeder::EMAIL)->value('id')
                ?? DB::table('users')->min('id');

            $originalName = $srcPath ? basename($srcPath) : basename($relativePath);

            if (! $this->dryRun) {
                DB::table('order_files')->insertOrIgnore([
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                    'user_id' => $userId,
                    'comment_id' => null,
                    'path' => $destPath,
                    'original_name' => $originalName,
                    'mime_type' => $fileExists ? ($attachment->post_mime_type ?? null) : null,
                    'size' => $fileExists && $srcPath ? (filesize($srcPath) ?: null) : null,
                    'type' => 'product_image',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $fileExists ? $this->copied++ : $this->pathsPreserved++;
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateCommentAttachments(): void
    {
        $this->line('');
        $this->line('--- Comment attachments (wp_commentmeta) ---');

        $userMap = DB::table('users')->pluck('id', 'email')->toArray();
        $deletedUserId = DB::table('users')->where('email', DeletedUserSeeder::EMAIL)->value('id');
        $fallbackUserId = $deletedUserId ?? DB::table('users')->min('id');
        $wpPostToOrderId = $this->buildWpPostToOrderIdMap();

        $baseQuery = DB::connection('legacy')
            ->table('commentmeta as cm')
            ->join('comments as c', 'c.comment_ID', '=', 'cm.comment_id')
            ->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
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
                            ->table('posts')
                            ->where('ID', (int) $row->attachment_id)
                            ->where('post_type', 'attachment')
                            ->first(['ID', 'guid', 'post_mime_type']);

                        if (! $attachment || empty($attachment->guid)) {
                            $this->missing++;

                            continue;
                        }

                        $relativePath = $this->guidToRelativePath($attachment->guid);
                        $destPath = 'orders/receipts/'.$relativePath;
                        $srcPath = $this->guidToLocalPath($attachment->guid);
                        $fileExists = $srcPath && is_file($srcPath);

                        if ($fileExists && ! $this->dryRun && Storage::disk('public')->exists($destPath)) {
                            $this->skipped++;

                            continue;
                        }

                        if ($fileExists && ! $this->dryRun) {
                            Storage::disk('public')->put($destPath, file_get_contents($srcPath));
                        }

                        $orderId = $wpPostToOrderId[(int) $row->comment_post_ID] ?? null;

                        if (! $orderId) {
                            $fileExists ? $this->copied++ : $this->pathsPreserved++;

                            continue;
                        }

                        $uploaderEmail = ! empty($row->comment_author_email) ? $row->comment_author_email : null;
                        $userId = ($uploaderEmail ? ($userMap[$uploaderEmail] ?? null) : null) ?? $fallbackUserId;

                        $originalName = $srcPath ? basename($srcPath) : basename($relativePath);

                        if (! $this->dryRun) {
                            DB::table('order_files')->insertOrIgnore([
                                'order_id' => $orderId,
                                'user_id' => $userId,
                                'comment_id' => null,
                                'path' => $destPath,
                                'original_name' => $originalName,
                                'mime_type' => $fileExists ? ($attachment->post_mime_type ?? null) : null,
                                'size' => $fileExists && $srcPath ? (filesize($srcPath) ?: null) : null,
                                'type' => 'receipt',
                                'created_at' => $row->comment_date ?? now(),
                                'updated_at' => $row->comment_date ?? now(),
                            ]);
                        }

                        $fileExists ? $this->copied++ : $this->pathsPreserved++;
                    } catch (\Exception $e) {
                        $this->errors++;
                    }
                }
            });

        $bar->finish();
        $this->newLine();
    }

    private function guidToLocalPath(string $guid): ?string
    {
        if (preg_match('#/uploads/(.+)$#', $guid, $m)) {
            return $this->uploadsPath.'/'.$m[1];
        }

        return null;
    }

    private function guidToRelativePath(string $guid): string
    {
        if (preg_match('#/uploads/(.+)$#', $guid, $m)) {
            return $m[1];
        }

        return basename($guid);
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
