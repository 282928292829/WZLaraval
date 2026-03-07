<?php

namespace App\Services;

use App\Models\ImageCleanupRun;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImageCleanupService
{
    public const PLACEHOLDER_PATH = 'images/deleted-placeholder.png';

    public const LOCK_KEY = 'image_cleanup_running';

    public const CHUNK_SIZE = 150;

    public function __construct(
        private ImageConversionService $imageConversion
    ) {}

    /**
     * Run cleanup: find eligible orders and process files (delete or compress).
     * Uses lock to prevent concurrent runs.
     *
     * @return array{orders_processed: int, files_deleted: int, files_compressed: int, bytes_freed: int, details: array}
     */
    public function run(bool $dryRun = false, string $triggeredBy = 'manual'): array
    {
        if (! $this->acquireLock()) {
            return [
                'orders_processed' => 0,
                'files_deleted' => 0,
                'files_compressed' => 0,
                'bytes_freed' => 0,
                'details' => [['error' => __('image_cleanup.already_running')]],
            ];
        }

        $run = ImageCleanupRun::create([
            'started_at' => now(),
            'dry_run' => $dryRun,
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $result = $this->execute($dryRun);
            $run->update([
                'finished_at' => now(),
                'orders_processed' => $result['orders_processed'],
                'files_deleted' => $result['files_deleted'],
                'files_compressed' => $result['files_compressed'],
                'bytes_freed' => $result['bytes_freed'],
                'details' => $result['details'],
            ]);

            return $result;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * @return array{orders_processed: int, files_deleted: int, files_compressed: int, bytes_freed: int, details: array}
     */
    protected function execute(bool $dryRun): array
    {
        $statuses = Setting::get('image_cleanup_statuses', []);
        if (empty($statuses) || ! is_array($statuses)) {
            return [
                'orders_processed' => 0,
                'files_deleted' => 0,
                'files_compressed' => 0,
                'bytes_freed' => 0,
                'details' => [['info' => __('image_cleanup.no_statuses_configured')]],
            ];
        }

        $action = Setting::get('image_cleanup_action', 'delete');
        $quality = (int) Setting::get('image_cleanup_compression_quality', 55);

        $ordersProcessed = 0;
        $filesDeleted = 0;
        $filesCompressed = 0;
        $bytesFreed = 0;
        $details = [];

        $query = Order::withTrashed()
            ->whereIn('status', $statuses)
            ->with(['files' => fn ($q) => $q->with('user')]);

        $query->chunk(self::CHUNK_SIZE, function ($orders) use (

            $action,
            $quality,
            $dryRun,
            &$ordersProcessed,
            &$filesDeleted,
            &$filesCompressed,
            &$bytesFreed,
            &$details
        ) {
            foreach ($orders as $order) {
                if (! $order->status_changed_at) {
                    continue;
                }

                $cutoff = $this->getCutoffForOrder($order);
                if ($cutoff === null) {
                    continue;
                }

                if ($order->status_changed_at > $cutoff) {
                    continue;
                }

                $orderResult = $this->processOrder($order, $action, $quality, $dryRun);
                if ($orderResult['processed']) {
                    $ordersProcessed++;
                    $filesDeleted += $orderResult['deleted'];
                    $filesCompressed += $orderResult['compressed'];
                    $bytesFreed += $orderResult['bytes_freed'];
                    if (! empty($orderResult['log'])) {
                        $details[] = array_merge(['order' => $order->order_number], $orderResult['log']);
                    }
                }
            }
        });

        return [
            'orders_processed' => $ordersProcessed,
            'files_deleted' => $filesDeleted,
            'files_compressed' => $filesCompressed,
            'bytes_freed' => $bytesFreed,
            'details' => $details,
        ];
    }

    /**
     * Get cutoff date for an order based on file type and uploader.
     * Returns the earliest cutoff among enabled types that apply to this order's files.
     */
    protected function getCutoffForOrder(Order $order): ?\Carbon\Carbon
    {
        $cutoffs = [];

        if (Setting::get('image_cleanup_customer_product', false)) {
            $cutoffs[] = now()->subDays((int) Setting::get('image_cleanup_retention_days_customer_product', 14));
        }
        if (Setting::get('image_cleanup_staff_product', false)) {
            $cutoffs[] = now()->subDays((int) Setting::get('image_cleanup_retention_days_staff_product', 90));
        }
        if (Setting::get('image_cleanup_customer_comment', false)) {
            $cutoffs[] = now()->subDays((int) Setting::get('image_cleanup_retention_days_customer_comment', 14));
        }
        if (Setting::get('image_cleanup_staff_comment', false)) {
            $cutoffs[] = now()->subDays((int) Setting::get('image_cleanup_retention_days_staff_comment', 90));
        }
        if (Setting::get('image_cleanup_receipt', false) || Setting::get('image_cleanup_invoice', false) || Setting::get('image_cleanup_other', false)) {
            $cutoffs[] = now()->subDays((int) Setting::get('image_cleanup_retention_days_customer_product', 14));
            $cutoffs[] = now()->subDays((int) Setting::get('image_cleanup_retention_days_staff_product', 90));
        }

        if (empty($cutoffs)) {
            return null;
        }

        return collect($cutoffs)->max();
    }

    /**
     * @return array{processed: bool, deleted: int, compressed: int, bytes_freed: int, log: array}
     */
    protected function processOrder(Order $order, string $action, int $quality, bool $dryRun): array
    {
        $deleted = 0;
        $compressed = 0;
        $bytesFreed = 0;
        $log = [];

        foreach ($order->files as $file) {
            if (! $this->shouldProcessFile($order, $file)) {
                continue;
            }

            $originalSize = $file->size ?? 0;

            if ($action === 'delete') {
                if (! $dryRun) {
                    $this->deleteFile($file, $order);
                }
                $deleted++;
                $bytesFreed += $originalSize;
                $log[] = ['file_id' => $file->id, 'path' => $file->path, 'action' => 'delete'];
            } elseif ($action === 'compress' && $file->isImage()) {
                if (! $dryRun) {
                    $newSize = $this->compressFile($file, $quality);
                    if ($newSize !== null) {
                        $bytesFreed += max(0, $originalSize - $newSize);
                        $file->update(['size' => $newSize]);
                    }
                } else {
                    $bytesFreed += (int) ($originalSize * 0.5);
                }
                $compressed++;
                $log[] = ['file_id' => $file->id, 'path' => $file->path, 'action' => 'compress'];
            }
        }

        return [
            'processed' => $deleted > 0 || $compressed > 0,
            'deleted' => $deleted,
            'compressed' => $compressed,
            'bytes_freed' => $bytesFreed,
            'log' => $log,
        ];
    }

    protected function shouldProcessFile(Order $order, OrderFile $file): bool
    {
        $statusChangedAt = $order->status_changed_at;
        if (! $statusChangedAt) {
            return false;
        }

        $user = $file->user;
        if (! $user) {
            return false;
        }
        $isStaff = $user->isStaffOrAbove();

        if ($file->type === 'product_image') {
            if ($isStaff && Setting::get('image_cleanup_staff_product', false)) {
                $retentionDays = (int) Setting::get('image_cleanup_retention_days_staff_product', 90);

                return $statusChangedAt->lte(now()->subDays($retentionDays));
            }
            if (! $isStaff && Setting::get('image_cleanup_customer_product', false)) {
                $retentionDays = (int) Setting::get('image_cleanup_retention_days_customer_product', 14);

                return $statusChangedAt->lte(now()->subDays($retentionDays));
            }
        }

        if ($file->type === 'comment') {
            if ($isStaff && Setting::get('image_cleanup_staff_comment', false)) {
                $retentionDays = (int) Setting::get('image_cleanup_retention_days_staff_comment', 90);

                return $statusChangedAt->lte(now()->subDays($retentionDays));
            }
            if (! $isStaff && Setting::get('image_cleanup_customer_comment', false)) {
                $retentionDays = (int) Setting::get('image_cleanup_retention_days_customer_comment', 14);

                return $statusChangedAt->lte(now()->subDays($retentionDays));
            }
        }

        if ($file->type === 'receipt' && Setting::get('image_cleanup_receipt', false)) {
            $retentionDays = $isStaff
                ? (int) Setting::get('image_cleanup_retention_days_staff_product', 90)
                : (int) Setting::get('image_cleanup_retention_days_customer_product', 14);

            return $statusChangedAt->lte(now()->subDays($retentionDays));
        }

        if ($file->type === 'invoice' && Setting::get('image_cleanup_invoice', false)) {
            $retentionDays = $isStaff
                ? (int) Setting::get('image_cleanup_retention_days_staff_product', 90)
                : (int) Setting::get('image_cleanup_retention_days_customer_product', 14);

            return $statusChangedAt->lte(now()->subDays($retentionDays));
        }

        if ($file->type === 'other' && Setting::get('image_cleanup_other', false)) {
            $retentionDays = $isStaff
                ? (int) Setting::get('image_cleanup_retention_days_staff_product', 90)
                : (int) Setting::get('image_cleanup_retention_days_customer_product', 14);

            return $statusChangedAt->lte(now()->subDays($retentionDays));
        }

        return false;
    }

    protected function deleteFile(OrderFile $file, Order $order): void
    {
        $path = $file->path;
        $wasPrimary = false;

        if ($file->type === 'product_image' && $file->order_item_id) {
            $item = OrderItem::where('order_id', $order->id)
                ->where('image_path', $path)
                ->first();
            if ($item) {
                $wasPrimary = true;
                $item->update(['image_path' => $this->ensurePlaceholderExists()]);
            }
        } elseif ($file->type === 'product_image') {
            OrderItem::where('order_id', $order->id)
                ->where('image_path', $path)
                ->update(['image_path' => $this->ensurePlaceholderExists()]);
        }

        Storage::disk('public')->delete($path);
        $file->delete();
    }

    protected function compressFile(OrderFile $file, int $quality): ?int
    {
        return $this->imageConversion->compressFile($file->path, 'public', $quality);
    }

    public function ensurePlaceholderExists(): string
    {
        $path = self::PLACEHOLDER_PATH;
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        Storage::disk('public')->makeDirectory(dirname($path));

        try {
            $imagick = new \Imagick;
            $imagick->newImage(200, 200, new \ImagickPixel(Setting::get('primary_color', '#f97316')));
            $imagick->setImageFormat('png');

            $draw = new \ImagickDraw;
            $draw->setFillColor('white');
            $draw->setFontSize(24);
            $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
            $imagick->annotateImage($draw, 100, 110, 0, 'Deleted');

            $blob = $imagick->getImageBlob();
            $imagick->destroy();
            $draw->destroy();

            Storage::disk('public')->put($path, $blob);

            return $path;
        } catch (\Throwable $e) {
            report($e);
            $fallback = 'images/deleted-placeholder-fallback.png';
            if (! Storage::disk('public')->exists($fallback)) {
                $imagick = new \Imagick;
                $imagick->newImage(200, 200, new \ImagickPixel('#9ca3af'));
                $imagick->setImageFormat('png');
                Storage::disk('public')->put($fallback, $imagick->getImageBlob());
                $imagick->destroy();
            }

            return $fallback;
        }
    }

    protected function acquireLock(): bool
    {
        return Cache::add(self::LOCK_KEY, true, now()->addHours(2));
    }

    protected function releaseLock(): void
    {
        Cache::forget(self::LOCK_KEY);
    }

    public function isLocked(): bool
    {
        return Cache::has(self::LOCK_KEY);
    }
}
