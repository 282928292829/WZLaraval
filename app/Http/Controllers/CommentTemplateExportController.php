<?php

namespace App\Http\Controllers;

use App\Models\CommentTemplate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommentTemplateExportController extends Controller
{
    /**
     * Export comment templates as CSV. Requires manage-comment-templates permission.
     */
    public function __invoke(): StreamedResponse
    {
        $this->authorize('manage-comment-templates');

        $templates = CommentTemplate::query()
            ->orderBy('sort_order')
            ->orderBy('usage_count', 'desc')
            ->get();

        $filename = 'comment-templates-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($templates): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [__('Title'), __('Content'), __('Order'), __('Uses')]);

            foreach ($templates as $t) {
                fputcsv($handle, [$t->title, $t->content, $t->sort_order, $t->usage_count]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
