<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class OrderExport implements FromCollection, WithDrawings, WithEvents, WithHeadings
{
    public function __construct(
        private readonly Order $order
    ) {}

    public function collection(): Collection
    {
        $items = $this->order->items()->orderBy('sort_order')->get();

        $rows = collect();
        foreach ($items as $i => $item) {
            $unitPrice = $item->unit_price && $item->currency
                ? number_format((float) $item->unit_price, 2).' '.$item->currency
                : '';
            $finalPrice = $item->final_price
                ? number_format((float) $item->final_price, 2).' SAR'
                : '';

            $rows->push([
                $i + 1,
                $item->url,
                $item->qty,
                $item->color ?? '',
                $item->size ?? '',
                $item->notes ?? '',
                $unitPrice,
                $finalPrice,
                '', // Image embedded via WithDrawings
            ]);
        }

        return $rows;
    }

    public function drawings(): array
    {
        $items = $this->order->items()->orderBy('sort_order')->get();
        $productImageFiles = $this->order->files
            ->whereNull('comment_id')
            ->where('type', 'product_image')
            ->filter(fn ($f) => str_starts_with($f->mime_type ?? '', 'image/'))
            ->values();
        $productImageIdx = 0;
        $drawings = [];

        foreach ($items as $i => $item) {
            $imagePath = null;
            if ($item->image_path) {
                $fullPath = Storage::disk('public')->path($item->image_path);
                if (file_exists($fullPath)) {
                    $imagePath = $fullPath;
                }
            } elseif ($productImageFiles->has($productImageIdx)) {
                $file = $productImageFiles[$productImageIdx];
                $fullPath = Storage::disk('public')->path($file->path);
                if (file_exists($fullPath)) {
                    $imagePath = $fullPath;
                }
                $productImageIdx++;
            }

            if ($imagePath) {
                $drawing = new Drawing;
                $drawing->setPath($imagePath);
                $drawing->setHeight(80);
                $drawing->setCoordinates('I'.($i + 2)); // Row 2 = header, data starts row 2
                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        $itemCount = $this->order->items()->count();

        return [
            AfterSheet::class => function (AfterSheet $event) use ($itemCount) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('I')->setWidth(18);
                for ($row = 2; $row <= $itemCount + 1; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(65);
                }
            },
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            __('orders.product'),
            __('orders.qty'),
            __('orders.color'),
            __('orders.size'),
            __('orders.notes'),
            __('orders.price'),
            __('orders.final'),
            __('orders.image'),
        ];
    }
}
