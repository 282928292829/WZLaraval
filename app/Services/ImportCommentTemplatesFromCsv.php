<?php

namespace App\Services;

use App\Models\CommentTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportCommentTemplatesFromCsv
{
    /**
     * Import comment templates from a CSV file.
     * Expected columns: title, content, sort_order (optional, default 0), usage_count (optional, default 0).
     * First row is header. UTF-8 encoded.
     */
    public function import(UploadedFile|string $file, bool $replaceExisting = false): array
    {
        $path = $file instanceof UploadedFile
            ? $file->getRealPath()
            : $this->resolveStoredPath($file);

        if ($path === null || ! file_exists($path) || ! is_readable($path)) {
            return [
                'success' => false,
                'message' => __('comment_templates.csv_file_invalid'),
                'count' => 0,
            ];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'success' => false,
                'message' => __('comment_templates.csv_file_invalid'),
                'count' => 0,
            ];
        }

        // UTF-8 BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return [
                'success' => false,
                'message' => __('comment_templates.csv_empty'),
                'count' => 0,
            ];
        }

        $header = array_map('trim', array_map('mb_strtolower', $header));
        $titleIdx = $this->findColumnIndex($header, ['title', 'اسم', 'name']);
        $contentIdx = $this->findColumnIndex($header, ['content', 'body', 'text', 'محتوى']);
        $sortOrderIdx = $this->findColumnIndex($header, ['sort_order', 'order', 'ترتيب']);
        $usageCountIdx = $this->findColumnIndex($header, ['usage_count', 'uses', 'الاستخدامات']);

        if ($titleIdx === null || $contentIdx === null) {
            fclose($handle);

            return [
                'success' => false,
                'message' => __('comment_templates.csv_columns_required'),
                'count' => 0,
            ];
        }

        if ($replaceExisting) {
            CommentTemplate::query()->delete();
        }

        $imported = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $title = trim((string) ($row[$titleIdx] ?? ''));
            $content = trim((string) ($row[$contentIdx] ?? ''));
            if ($title === '' && $content === '') {
                continue;
            }
            if ($title === '') {
                $errors[] = __('comment_templates.csv_row_missing_title', ['row' => $rowNum]);

                continue;
            }
            $sortOrder = $sortOrderIdx !== null && isset($row[$sortOrderIdx])
                ? (int) $row[$sortOrderIdx]
                : 0;
            $usageCount = $usageCountIdx !== null && isset($row[$usageCountIdx])
                ? (int) $row[$usageCountIdx]
                : 0;

            CommentTemplate::create([
                'title' => $title,
                'content' => $content,
                'sort_order' => max(0, $sortOrder),
                'usage_count' => max(0, $usageCount),
                'is_active' => true,
            ]);
            $imported++;
        }

        fclose($handle);

        $message = $imported > 0
            ? __('comment_templates.csv_import_success', ['count' => $imported])
            : __('comment_templates.csv_import_none');
        if (count($errors) > 0) {
            $message .= ' '.implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' …';
            }
        }

        return [
            'success' => true,
            'message' => $message,
            'count' => $imported,
            'errors' => $errors,
        ];
    }

    private function resolveStoredPath(string $path): ?string
    {
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, '/') && file_exists($path)) {
            return $path;
        }
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->path($path);
            }
        }

        return null;
    }

    private function findColumnIndex(array $header, array $candidates): ?int
    {
        foreach ($candidates as $c) {
            $idx = array_search($c, $header, true);
            if ($idx !== false) {
                return $idx;
            }
        }

        return null;
    }
}
