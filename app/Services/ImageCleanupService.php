<?php

namespace App\Services;

use App\Models\ImageCleanupRule;
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
     * Run cleanup for a rule type (delete or compress). Uses rules from image_cleanup_rules table.
     * Uses lock to prevent concurrent runs.
     *
     * @return array{orders_processed: int, files_deleted: int, files_compressed: int, bytes_freed: int, details: array}
     */
    public function run(string $ruleType, bool $dryRun = false, string $triggeredBy = 'manual', ?int $ruleId = null): array
    {
        if (! in_array($ruleType, [ImageCleanupRule::TYPE_DELETE, ImageCleanupRule::TYPE_COMPRESS], true)) {
            return [
                'orders_processed' => 0,
                'files_deleted' => 0,
                'files_compressed' => 0,
                'bytes_freed' => 0,
                'details' => [['error' => __('image_cleanup.invalid_rule_type')]],
            ];
        }

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
            'rule_type' => $ruleType,
            'image_cleanup_rule_id' => $ruleId,
            'started_at' => now(),
            'dry_run' => $dryRun,
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $result = $this->executeByRules($ruleType, $dryRun);
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
    protected function executeByRules(string $ruleType, bool $dryRun): array
    {
        $rules = ImageCleanupRule::where('rule_type', $ruleType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($rules->isEmpty()) {
            return [
                'orders_processed' => 0,
                'files_deleted' => 0,
                'files_compressed' => 0,
                'bytes_freed' => 0,
                'details' => [['info' => __('image_cleanup.no_rules_configured')]],
            ];
        }

        $allStatuses = $rules->pluck('statuses')->flatten()->unique()->filter()->values()->all();
        if (empty($allStatuses)) {
            return [
                'orders_processed' => 0,
                'files_deleted' => 0,
                'files_compressed' => 0,
                'bytes_freed' => 0,
                'details' => [['info' => __('image_cleanup.no_statuses_configured')]],
            ];
        }

        $ordersProcessed = 0;
        $filesDeleted = 0;
        $filesCompressed = 0;
        $bytesFreed = 0;
        $details = [];

        $query = Order::withTrashed()
            ->whereIn('status', $allStatuses)
            ->with(['files' => fn ($q) => $q->with('user')]);

        $query->chunk(self::CHUNK_SIZE, function ($orders) use (
            $rules,
            $ruleType,
            $dryRun,
            &$ordersProcessed,
            &$filesDeleted,
            &$filesCompressed,
            &$bytesFreed,
            &$details
        ) {
            foreach ($orders as $order) {
                $orderResult = $this->processOrderWithRules($order, $rules, $ruleType, $dryRun);
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
     * @param  \Illuminate\Support\Collection<int, ImageCleanupRule>  $rules
     * @return array{processed: bool, deleted: int, compressed: int, bytes_freed: int, log: array}
     */
    protected function processOrderWithRules(Order $order, $rules, string $ruleType, bool $dryRun): array
    {
        $deleted = 0;
        $compressed = 0;
        $bytesFreed = 0;
        $log = [];

        foreach ($order->files as $file) {
            $matchingRule = $this->findMatchingRule($order, $file, $rules);
            if ($matchingRule === null) {
                continue;
            }

            $originalSize = $file->size ?? 0;

            if ($ruleType === ImageCleanupRule::TYPE_DELETE) {
                if (! $dryRun) {
                    $this->deleteFile($file, $order);
                }
                $deleted++;
                $bytesFreed += $originalSize;
                $log[] = ['file_id' => $file->id, 'path' => $file->path, 'action' => 'delete'];
            } elseif ($ruleType === ImageCleanupRule::TYPE_COMPRESS && $file->isImage()) {
                $quality = $matchingRule->compression_quality ?? 55;
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

    /**
     * Find the first rule that matches this file for the order.
     *
     * @param  \Illuminate\Support\Collection<int, ImageCleanupRule>  $rules
     */
    protected function findMatchingRule(Order $order, OrderFile $file, $rules): ?ImageCleanupRule
    {
        foreach ($rules as $rule) {
            if (! in_array($order->status, $rule->statuses ?? [], true)) {
                continue;
            }
            if ($rule->shouldProcessFile($order, $file)) {
                return $rule;
            }
        }

        return null;
    }

    protected function deleteFile(OrderFile $file, Order $order): void
    {
        $path = $file->path;

        if ($file->type === 'product_image') {
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
