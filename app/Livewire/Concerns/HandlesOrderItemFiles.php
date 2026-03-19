<?php

namespace App\Livewire\Concerns;

trait HandlesOrderItemFiles
{
    public function removeItemFile(int $itemIndex, int $fileIndex): void
    {
        $files = $this->itemFiles[$itemIndex] ?? null;
        if ($files === null) {
            return;
        }
        $arr = is_array($files) ? array_values($files) : ($files ? [$files] : []);
        array_splice($arr, $fileIndex, 1);
        $this->itemFiles[$itemIndex] = $arr;
    }

    public function shiftFileIndex(int $removedIndex): void
    {
        $shifted = [];
        foreach ($this->itemFiles as $idx => $files) {
            if ($idx === $removedIndex) {
                continue;
            }
            $newIdx = $idx > $removedIndex ? $idx - 1 : $idx;
            $shifted[$newIdx] = is_array($files) ? array_values($files) : ($files ? [$files] : []);
        }
        $this->itemFiles = $shifted;
    }

    /**
     * Normalize itemFiles to array-of-arrays. Ensures each item has [file, ...].
     *
     * @return array<int, array<int, mixed>>
     */
    protected function normalizeItemFiles(): array
    {
        $normalized = [];
        foreach ($this->itemFiles as $idx => $files) {
            if (is_array($files)) {
                $normalized[$idx] = array_values(array_filter($files));
            } elseif ($files) {
                $normalized[$idx] = [$files];
            } else {
                $normalized[$idx] = [];
            }
        }

        return $normalized;
    }

    /**
     * Get preview data for an item's attached files (for cart drawer thumbnails).
     *
     * @return array<int, array{url: string|null, type: 'img'|'pdf'|'other'}>
     */
    public function getItemFilePreviews(int $itemIndex): array
    {
        $normalized = $this->normalizeItemFiles();
        $files = $normalized[$itemIndex] ?? [];

        $previews = [];
        $maxDataUrlBytes = 2 * 1024 * 1024; // 2MB cap for base64 fallback

        foreach ($files as $file) {
            if (! is_object($file)) {
                continue;
            }
            $mime = $file->getMimeType() ?? '';
            $type = str_starts_with($mime, 'image/') ? 'img' : (str_contains($mime, 'pdf') ? 'pdf' : 'other');

            $url = null;

            // Try Livewire temporaryUrl first (works with S3, etc.)
            if (method_exists($file, 'temporaryUrl')) {
                try {
                    if (method_exists($file, 'hasTemporaryUrl') && $file->hasTemporaryUrl()) {
                        $url = $file->temporaryUrl();
                    } elseif (! method_exists($file, 'hasTemporaryUrl')) {
                        $url = $file->temporaryUrl();
                    }
                } catch (\Throwable) {
                    // temporaryUrl may fail on local disk
                }
            }

            // Fallback for images when temporaryUrl fails (e.g. local disk)
            if (! $url && $type === 'img') {
                $path = $file->getRealPath();
                if ($path && is_readable($path) && filesize($path) <= $maxDataUrlBytes) {
                    $data = base64_encode((string) file_get_contents($path));
                    $url = 'data:'.($mime ?: 'image/png').';base64,'.$data;
                }
            }

            $previews[] = ['url' => $url, 'type' => $type];
        }

        return $previews;
    }

    /**
     * Count total files and check per-item / per-order limits.
     *
     * @return array{valid: bool, per_item_violation: ?int, total: int}
     */
    protected function checkFileLimits(): array
    {
        $normalized = $this->normalizeItemFiles();
        $total = 0;
        $perItemViolation = null;

        foreach ($normalized as $idx => $files) {
            $count = count($files);
            $total += $count;
            if ($count > $this->maxImagesPerItem) {
                $perItemViolation = $idx;
            }
        }

        $valid = $total <= $this->maxImagesPerOrder && $perItemViolation === null;

        return ['valid' => $valid, 'per_item_violation' => $perItemViolation, 'total' => $total];
    }
}
