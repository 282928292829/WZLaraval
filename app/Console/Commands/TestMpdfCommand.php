<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mpdf\Mpdf;

class TestMpdfCommand extends Command
{
    protected $signature = 'test:mpdf';

    protected $description = 'Generate a test PDF with mPDF (Arabic RTL) and save to storage.';

    public function handle(): int
    {
        $outPath = storage_path('app/public/mpdf-test.pdf');

        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle('mPDF test – Arabic');

            $html = '
            <h1 style="border-bottom: 2px solid #f97316; padding-bottom: 10px;">فاتورة تجريبية / Test Invoice</h1>
            <p><strong>بسم الله الرحمن الرحيم</strong></p>
            <p>هذا نص عربي للتأكد من أن الحروف متصلة بشكل صحيح.</p>
            <p>Order #900041 · 2025-02-24</p>
            <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
                <tr style="background: #f3f4f6;">
                    <th style="padding: 8px; text-align: right;">المنتج</th>
                    <th style="padding: 8px;">الكمية</th>
                    <th style="padding: 8px;">السعر</th>
                </tr>
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">منتج تجريبي</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">2</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">150.00 SAR</td>
                </tr>
            </table>
            <p style="margin-top: 20px;"><strong>الإجمالي: 300.00 SAR</strong></p>
            ';

            $mpdf->WriteHTML($html);

            if (! is_dir(dirname($outPath))) {
                mkdir(dirname($outPath), 0755, true);
            }
            $mpdf->Output($outPath, \Mpdf\Output\Destination::FILE);

            $this->info('PDF saved: '.$outPath);
            $this->info('Size: '.number_format(filesize($outPath) / 1024, 1).' KB');
            $this->newLine();
            $this->line('Open via: php artisan storage:link (if needed) then visit /storage/mpdf-test.pdf');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('mPDF error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
