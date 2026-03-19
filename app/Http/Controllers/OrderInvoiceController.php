<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
use App\Http\Requests\Order\GenerateInvoiceRequest;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CommissionCalculator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderInvoiceController extends Controller
{
    /**
     * Generate an invoice PDF and optionally attach it to a comment.
     *
     * Note: PDF is built synchronously. For heavy invoices (Items Cost / General with many
     * lines, or 'both' language), consider queueing PDF generation to avoid request timeouts.
     * A queued job would need to notify staff when ready (e.g. via comment or download link).
     */
    public function generateInvoice(GenerateInvoiceRequest $request, Order $order)
    {
        $this->authorize('generate-pdf-invoice');

        $order->load(['items', 'user']);
        $validated = $request->validated();
        $action = $validated['action'] ?? 'publish';

        $invoiceType = $validated['invoice_type'] ?? InvoiceType::FirstPayment->value;
        $notes = $validated['custom_notes'] ?? '';
        $showOriginalCurrency = filter_var($validated['show_original_currency'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $invoiceLanguage = $validated['invoice_language'] ?? $order->user?->locale ?? config('app.locale', 'ar');
        if (! in_array($invoiceLanguage, ['ar', 'en', 'both'], true)) {
            $invoiceLanguage = 'ar';
        }

        // Auto-fill first_agent_fee when not overridden (First Payment only)
        if ($invoiceType === InvoiceType::FirstPayment->value) {
            $firstItemsTotal = (float) ($validated['first_items_total'] ?? 0);
            $firstAgentFee = (float) ($validated['first_agent_fee'] ?? 0);
            $overridden = filter_var($validated['first_commission_overridden'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($firstItemsTotal > 0 && ! $overridden && $firstAgentFee <= 0) {
                $validated['first_agent_fee'] = CommissionCalculator::calculate($firstItemsTotal);
            }
        }

        $extra = $this->buildInvoiceExtra($validated, $invoiceType);

        $invoiceCount = $order->files()->where('type', 'invoice')->count() + 1;
        $filename = $this->resolveInvoiceFilename(
            $order,
            $invoiceType,
            $invoiceCount,
            $validated['custom_filename'] ?? null
        );

        $settings = $this->invoiceSettings();
        $invoiceNumber = $this->resolveInvoiceNumber($order, $invoiceType, $invoiceCount);

        try {
            $pdfContent = $this->buildInvoicePdf(
                $order,
                $validated,
                $invoiceType,
                $notes,
                $filename,
                $extra,
                $settings,
                $invoiceLanguage,
                $showOriginalCurrency,
                $invoiceCount,
                $invoiceNumber
            );
        } catch (\Throwable $e) {
            Log::error('Invoice PDF generation failed', [
                'order_id' => $order->id,
                'invoice_type' => $invoiceType,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('orders.show', $order)
                ->with('error', __('orders.invoice_generation_failed'));
        }

        if ($action === 'preview') {
            return response()->streamDownload(
                fn () => print $pdfContent,
                $filename,
                ['Content-Type' => 'application/pdf'],
                'attachment'
            );
        }

        $dir = 'order-files/'.$order->id;
        $path = $dir.'/'.$filename;
        $fullPath = storage_path('app/public/'.$path);

        if (! is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }
        file_put_contents($fullPath, $pdfContent);

        $firstLocale = $invoiceLanguage === 'both' ? 'ar' : $invoiceLanguage;
        [, $total] = $this->buildInvoiceLinesForLocale($validated, $order, $invoiceType, $showOriginalCurrency, $firstLocale, $extra);

        $commentBody = $this->resolveInvoiceCommentMessage(
            $validated['comment_message'] ?? '',
            $total,
            $order,
            $invoiceType,
            $validated
        );

        $comment = $order->comments()->create([
            'user_id' => auth()->id(),
            'body' => $commentBody,
            'is_internal' => false,
        ]);

        $order->files()->create([
            'user_id' => auth()->id(),
            'comment_id' => $comment->id,
            'path' => $path,
            'original_name' => $filename,
            'mime_type' => 'application/pdf',
            'size' => (int) strlen($pdfContent),
            'type' => 'invoice',
        ]);

        if (in_array($invoiceType, [InvoiceType::FirstPayment->value, InvoiceType::SecondFinal->value], true)) {
            $updates = [];
            if ($invoiceType === InvoiceType::FirstPayment->value && isset($validated['first_agent_fee'])) {
                $v = (float) $validated['first_agent_fee'];
                if ($v > 0) {
                    $updates['agent_fee'] = $v;
                }
            }
            if ($invoiceType === InvoiceType::SecondFinal->value) {
                if (isset($validated['second_agent_fee'])) {
                    $v = (float) $validated['second_agent_fee'];
                    if ($v > 0) {
                        $updates['agent_fee'] = $v;
                    }
                }
                if (isset($validated['second_shipping_cost'])) {
                    $v = (float) $validated['second_shipping_cost'];
                    if ($v > 0) {
                        $updates['international_shipping'] = $v;
                    }
                }
            }
            if (! empty($updates)) {
                $order->update($updates);
            }
        }

        return redirect()->route('orders.show', $order)->with('success', __('orders.invoice_generated'));
    }

    /** @return array<string, mixed> */
    private function invoiceSettings(): array
    {
        $siteName = Setting::get('site_name') ?: config('app.name');
        $invoiceSiteName = trim((string) Setting::get('invoice_site_name', ''));
        $invoiceLogo = Setting::get('invoice_logo', '');
        $logoImage = Setting::get('logo_image', '');
        $rawLogo = $invoiceLogo ?: $logoImage;
        $logoPath = is_array($rawLogo) ? ($rawLogo[0] ?? '') : (string) $rawLogo;

        $fullLogoPath = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $fullLogoPath = Storage::disk('public')->path($logoPath);
        }

        $invoiceCustomLines = Setting::get('invoice_custom_lines', []);
        $customLines = is_array($invoiceCustomLines) ? $invoiceCustomLines : [];

        $invoiceCompanyDetails = Setting::get('invoice_company_details', []);
        $companyDetails = is_array($invoiceCompanyDetails) ? $invoiceCompanyDetails : [];

        $invoiceTypeLabels = Setting::get('invoice_type_labels', []);
        $typeLabels = is_array($invoiceTypeLabels) ? $invoiceTypeLabels : [];

        return [
            'site_name' => $invoiceSiteName !== '' ? $invoiceSiteName : $siteName,
            'logo_path' => $fullLogoPath,
            'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'show_order_number' => (bool) Setting::get('invoice_show_order_number', true),
            'show_customer_name' => (bool) Setting::get('invoice_show_customer_name', true),
            'show_email' => (bool) Setting::get('invoice_show_email', true),
            'show_phone' => (bool) Setting::get('invoice_show_phone', true),
            'invoice_greeting' => trim((string) Setting::get('invoice_greeting', '')),
            'invoice_confirmation' => trim((string) Setting::get('invoice_confirmation', '')),
            'invoice_payment_instructions' => trim((string) Setting::get('invoice_payment_instructions', '')),
            'invoice_footer_text' => trim((string) Setting::get('invoice_footer_text', '')),
            'invoice_show_order_items' => (bool) Setting::get('invoice_show_order_items', false),
            'invoice_custom_lines' => $customLines,
            'primary_color' => trim((string) Setting::get('primary_color', '#f97316')) ?: '#f97316',
            'invoice_number_pattern' => trim((string) Setting::get('invoice_number_pattern', '{order_number}-{count}')),
            'invoice_show_type_label' => (bool) Setting::get('invoice_show_type_label', false),
            'invoice_type_labels' => $typeLabels,
            'invoice_show_company_details' => (bool) Setting::get('invoice_show_company_details', false),
            'invoice_company_details' => $companyDetails,
            'invoice_show_due_date' => (bool) Setting::get('invoice_show_due_date', false),
            'invoice_due_date_days' => (int) Setting::get('invoice_due_date_days', 7),
            'invoice_due_date_label' => trim((string) Setting::get('invoice_due_date_label', '')) ?: __('orders.invoice_due_date'),
        ];
    }

    /** @return array<string, mixed> */
    private function buildInvoiceExtra(array $validated, string $invoiceType): array
    {
        $extra = [];
        if ($invoiceType === InvoiceType::SecondFinal->value) {
            $productValue = (float) ($validated['second_product_value'] ?? 0);
            $agentFee = (float) ($validated['second_agent_fee'] ?? 0);
            $shippingCost = (float) ($validated['second_shipping_cost'] ?? 0);
            $firstPayment = (float) ($validated['second_first_payment'] ?? 0);
            $remaining = (float) ($validated['second_remaining'] ?? 0);
            $total = $productValue + $agentFee + $shippingCost;
            $extra['weight'] = $validated['second_weight'] ?? '';
            $extra['shipping_company'] = $validated['second_shipping_company'] ?? '';
            $extra['first_payment'] = $firstPayment;
            $extra['remaining'] = $remaining > 0 ? $remaining : max(0, $total - $firstPayment);

            $showOrderItems = isset($validated['show_order_items'])
                ? filter_var($validated['show_order_items'], FILTER_VALIDATE_BOOLEAN)
                : (bool) Setting::get('invoice_show_order_items', false);
            $extra['show_order_items'] = $showOrderItems;

            $formCustomLines = $validated['custom_lines'] ?? null;
            if (is_array($formCustomLines) && count($formCustomLines) > 0) {
                $lines = [];
                foreach ($formCustomLines as $cl) {
                    $label = trim($cl['label'] ?? '');
                    $amount = (float) ($cl['amount'] ?? 0);
                    $visible = isset($cl['visible']) ? filter_var($cl['visible'], FILTER_VALIDATE_BOOLEAN) : true;
                    if ($label !== '' || $amount > 0) {
                        $lines[] = ['label' => $label, 'amount' => $amount, 'visible' => $visible];
                    }
                }
                $extra['custom_lines'] = $lines;
            } else {
                $settingsLines = Setting::get('invoice_custom_lines', []);
                $extra['custom_lines'] = is_array($settingsLines) ? $settingsLines : [];
            }
        }

        return $extra;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $settings
     */
    private function buildInvoiceLinesForLocale(array $validated, Order $order, string $invoiceType, bool $showOriginalCurrency, string $locale, array $extra = []): array
    {
        $originalLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $lines = [];
            $total = 0.0;

            switch ($invoiceType) {
                case InvoiceType::FirstPayment->value:
                    $itemsTotal = (float) ($validated['first_items_total'] ?? 0);
                    $agentFee = (float) ($validated['first_agent_fee'] ?? 0);
                    $lines[] = ['description' => __('orders.invoice_items_total'), 'qty' => 1, 'unit_price' => number_format($itemsTotal, 2), 'line_total' => $itemsTotal, 'is_fee' => false];
                    if ($agentFee > 0) {
                        $lines[] = ['description' => __('orders.fee_agent_fee'), 'qty' => 1, 'unit_price' => number_format($agentFee, 2), 'line_total' => $agentFee, 'is_fee' => true];
                    }
                    $otherLabel = trim((string) ($validated['first_other_label'] ?? ''));
                    $otherAmount = (float) ($validated['first_other_amount'] ?? 0);
                    if ($otherAmount > 0) {
                        $lines[] = ['description' => $otherLabel !== '' ? $otherLabel : __('orders.invoice_other'), 'qty' => 1, 'unit_price' => number_format($otherAmount, 2), 'line_total' => $otherAmount, 'is_fee' => true];
                    }
                    $extras = $validated['first_extras'] ?? [];
                    foreach (is_array($extras) ? $extras : [] as $e) {
                        $label = trim($e['label'] ?? '');
                        $amt = (float) ($e['amount'] ?? 0);
                        if ($label !== '' && $amt > 0) {
                            $lines[] = ['description' => $label, 'qty' => 1, 'unit_price' => number_format($amt, 2), 'line_total' => $amt, 'is_fee' => true];
                        }
                    }
                    $total = array_sum(array_column($lines, 'line_total'));
                    if (filter_var($validated['first_total_overridden'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        $overrideTotal = (float) ($validated['first_total'] ?? 0);
                        if ($overrideTotal > 0) {
                            $total = $overrideTotal;
                        }
                    }
                    break;

                case InvoiceType::SecondFinal->value:
                    $productValue = (float) ($validated['second_product_value'] ?? 0);
                    $agentFee = (float) ($validated['second_agent_fee'] ?? 0);
                    $shippingCost = (float) ($validated['second_shipping_cost'] ?? 0);
                    $showOrderItems = (bool) ($extra['show_order_items'] ?? false);
                    $customLines = $extra['custom_lines'] ?? [];
                    $customLines = is_array($customLines) ? $customLines : [];

                    if ($productValue > 0) {
                        $lines[] = ['description' => __('orders.invoice_product_value'), 'qty' => 1, 'unit_price' => number_format($productValue, 2), 'line_total' => $productValue, 'is_fee' => false, 'is_sub_item' => false];
                        if ($showOrderItems && $order->items->isNotEmpty()) {
                            foreach ($order->items as $item) {
                                $priceSar = (float) ($item->final_price ?? $item->unit_price ?? 0);
                                $lineTotalSar = $priceSar * ($item->qty ?? 1);
                                if ($lineTotalSar > 0) {
                                    $desc = $item->url
                                        ? (strlen($item->url) > 60 ? substr($item->url, 0, 57).'...' : $item->url)
                                        : ($item->notes ?: __('orders.item').' #'.(count($lines) + 1));
                                    $lines[] = [
                                        'description' => $desc,
                                        'qty' => $item->qty,
                                        'unit_price' => number_format($priceSar, 2),
                                        'line_total' => $lineTotalSar,
                                        'is_fee' => false,
                                        'is_sub_item' => true,
                                    ];
                                }
                            }
                        }
                    }
                    foreach ($customLines as $cl) {
                        $visible = isset($cl['visible']) ? filter_var($cl['visible'], FILTER_VALIDATE_BOOLEAN) : true;
                        if (! $visible) {
                            continue;
                        }
                        $label = trim($cl['label'] ?? '');
                        $amt = (float) ($cl['amount'] ?? 0);
                        if ($label !== '' || $amt > 0) {
                            $lines[] = ['description' => $label ?: __('orders.invoice_line_item'), 'qty' => 1, 'unit_price' => number_format($amt, 2), 'line_total' => $amt, 'is_fee' => true, 'is_sub_item' => false];
                        }
                    }
                    if ($agentFee > 0) {
                        $lines[] = ['description' => __('orders.fee_agent_fee'), 'qty' => 1, 'unit_price' => number_format($agentFee, 2), 'line_total' => $agentFee, 'is_fee' => true, 'is_sub_item' => false];
                    }
                    if ($shippingCost > 0) {
                        $lines[] = ['description' => __('orders.invoice_shipping_cost'), 'qty' => 1, 'unit_price' => number_format($shippingCost, 2), 'line_total' => $shippingCost, 'is_fee' => true, 'is_sub_item' => false];
                    }
                    $total = $productValue + $agentFee + $shippingCost;
                    foreach ($customLines as $cl) {
                        $visible = isset($cl['visible']) ? filter_var($cl['visible'], FILTER_VALIDATE_BOOLEAN) : true;
                        if ($visible) {
                            $total += (float) ($cl['amount'] ?? 0);
                        }
                    }
                    break;

                case InvoiceType::ItemsCost->value:
                    $formItems = $validated['items'] ?? [];
                    if (! empty($formItems)) {
                        foreach ($formItems as $it) {
                            $desc = trim($it['description'] ?? '');
                            $qty = (int) ($it['qty'] ?? 1) ?: 1;
                            $unitPrice = (float) ($it['unit_price'] ?? 0);
                            $currency = trim($it['currency'] ?? 'SAR') ?: 'SAR';
                            $lineTotal = $unitPrice * $qty;
                            if ($lineTotal > 0 || $desc !== '') {
                                $lines[] = [
                                    'description' => $desc ?: __('orders.item').' #'.(count($lines) + 1),
                                    'qty' => $qty,
                                    'unit_price' => number_format($unitPrice, 2),
                                    'unit_price_original' => $unitPrice,
                                    'currency' => $currency,
                                    'line_total' => $lineTotal,
                                    'show_original' => $showOriginalCurrency && $currency !== 'SAR' && $unitPrice > 0,
                                    'is_fee' => false,
                                ];
                            }
                        }
                    } else {
                        foreach ($order->items as $item) {
                            $priceSar = (float) ($item->final_price ?? $item->unit_price ?? 0);
                            $lineTotalSar = $priceSar * ($item->qty ?? 1);
                            if ($lineTotalSar > 0) {
                                $desc = $item->url
                                    ? (strlen($item->url) > 60 ? substr($item->url, 0, 57).'...' : $item->url)
                                    : ($item->notes ?: __('orders.item').' #'.(count($lines) + 1));
                                $currency = $item->currency ?: 'SAR';
                                $unitOriginal = (float) ($item->unit_price ?? 0);
                                $lines[] = [
                                    'description' => $desc,
                                    'qty' => $item->qty,
                                    'unit_price' => number_format($priceSar, 2),
                                    'unit_price_original' => $unitOriginal,
                                    'currency' => $currency,
                                    'line_total' => $lineTotalSar,
                                    'show_original' => $showOriginalCurrency && $currency !== 'SAR' && $unitOriginal > 0,
                                    'is_fee' => false,
                                ];
                            }
                        }
                    }
                    $total = array_sum(array_column($lines, 'line_total'));
                    break;

                case InvoiceType::General->value:
                    $generalLines = $validated['general_lines'] ?? [];
                    foreach (is_array($generalLines) ? $generalLines : [] as $gl) {
                        $label = trim($gl['label'] ?? '');
                        $amt = (float) ($gl['amount'] ?? 0);
                        if ($label !== '' || $amt > 0) {
                            $lines[] = ['description' => $label ?: __('orders.invoice_line_item'), 'qty' => 1, 'unit_price' => number_format($amt, 2), 'line_total' => $amt, 'is_fee' => false];
                        }
                    }
                    $total = array_sum(array_column($lines, 'line_total'));
                    break;

                default:
                    $total = (float) ($validated['custom_amount'] ?? 0);
                    if ($total > 0) {
                        $lines[] = ['description' => __('orders.invoice_total'), 'qty' => 1, 'unit_price' => number_format($total, 2), 'line_total' => $total, 'is_fee' => false];
                    }
            }

            return [$lines, $total];
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $settings
     */
    private function buildInvoicePdf(
        Order $order,
        array $validated,
        string $invoiceType,
        string $notes,
        string $filename,
        array $extra,
        array $settings,
        string $invoiceLanguage,
        bool $showOriginalCurrency,
        int $invoiceCount = 1,
        string $invoiceNumber = ''
    ): string {
        $locales = $invoiceLanguage === 'both' ? ['ar', 'en'] : [$invoiceLanguage];
        $defaultConfig = (new \Mpdf\Config\ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new \Mpdf\Config\FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $fontPath = realpath(base_path('storage/fonts')) ?: base_path('storage/fonts');

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => storage_path('framework/cache'),
            'fontDir' => array_merge($fontDirs, [$fontPath]),
            'fontdata' => $fontData + [
                'ibmplexarabic' => [
                    'R' => 'IBMPlexSansArabic-Regular.ttf',
                    'B' => 'IBMPlexSansArabic-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => 'dejavusans',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);

        $firstLocale = $locales[0];
        $titleNumber = $invoiceNumber !== '' ? $invoiceNumber : $order->order_number;
        $mpdf->SetTitle($firstLocale === 'ar' ? 'فاتورة رقم '.$titleNumber : 'Invoice '.$titleNumber);

        foreach ($locales as $idx => $locale) {
            if ($idx > 0) {
                $mpdf->AddPage();
            }

            $isRtl = $locale === 'ar';
            $mpdf->SetDirectionality($isRtl ? 'rtl' : 'ltr');

            [$lines, $total] = $this->buildInvoiceLinesForLocale($validated, $order, $invoiceType, $showOriginalCurrency, $locale, $extra);

            $html = view('orders.invoice-pdf-mpdf', [
                'order' => $order,
                'lines' => $lines,
                'total' => $total,
                'invoiceType' => $invoiceType,
                'notes' => $notes,
                'isRtl' => $isRtl,
                'invoiceLocale' => $locale,
                'extra' => $extra,
                'settings' => $settings,
                'invoiceNumber' => $invoiceNumber,
                'invoiceCount' => $invoiceCount,
            ])->render();

            $mpdf->WriteHTML($html);
        }

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private function resolveInvoiceFilename(Order $order, string $invoiceType, int $invoiceCount, ?string $customOverride): string
    {
        $replacements = [
            '{order_number}' => $order->order_number,
            '{date}' => now()->format('Y-m-d'),
            '{type}' => $invoiceType,
            '{site_name}' => $this->sanitizeFilenamePart(Setting::get('site_name') ?: config('app.name')),
            '{count}' => (string) $invoiceCount,
        ];

        $pattern = $customOverride;
        if (trim((string) $pattern) === '') {
            $pattern = trim((string) Setting::get('invoice_filename_pattern', ''));
        }
        if (trim($pattern) === '') {
            $pattern = $invoiceCount > 1
                ? 'Invoice-{order_number}-{count}.pdf'
                : 'Invoice-{order_number}.pdf';
        }

        $filename = str_replace(array_keys($replacements), array_values($replacements), $pattern);
        $filename = $this->sanitizeFilename($filename);

        return $filename !== '' ? $filename : 'Invoice-'.$order->order_number.'.pdf';
    }

    private function resolveInvoiceNumber(Order $order, string $invoiceType, int $invoiceCount): string
    {
        $pattern = trim((string) Setting::get('invoice_number_pattern', '{order_number}-{count}'));
        if ($pattern === '') {
            return $invoiceCount > 1 ? $order->order_number.'-'.$invoiceCount : $order->order_number;
        }

        $replacements = [
            '{order_number}' => $order->order_number,
            '{count}' => (string) $invoiceCount,
            '{date}' => now()->format('Y-m-d'),
            '{type}' => $invoiceType,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^\p{L}\p{N}\s_\-\.\(\)]/u', '', $filename) ?? $filename;
        $filename = preg_replace('/\.\.+/', '.', $filename) ?? $filename;
        $filename = trim($filename, " \t\n\r\0\x0B/\\");

        return str_ends_with(strtolower($filename), '.pdf') ? $filename : $filename.'.pdf';
    }

    private function sanitizeFilenamePart(string $value): string
    {
        return preg_replace('/[^\p{L}\p{N}\s_\-]/u', '', $value) ?? $value;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveInvoiceCommentMessage(string $message, float $total, Order $order, string $invoiceType, array $validated = []): string
    {
        $text = trim($message);

        if ($text === '' && $invoiceType === InvoiceType::FirstPayment->value) {
            $template = trim((string) Setting::get('invoice_first_payment_comment_template', ''));
            if ($template === '') {
                $template = __('orders.invoice_first_payment_comment_default');
            }
            $subtotal = (float) ($validated['first_items_total'] ?? 0);
            $commission = (float) ($validated['first_agent_fee'] ?? 0);
            $whatsappDisplay = Setting::get('whatsapp', '') ?: '-';
            $replacements = [
                ':subtotal' => number_format($subtotal, 0, '.', ','),
                ':commission' => number_format($commission, 0, '.', ','),
                ':total' => number_format($total, 0, '.', ','),
                ':site_name' => Setting::get('site_name') ?: config('app.name'),
                ':whatsapp' => $whatsappDisplay,
            ];
            $text = str_replace(array_keys($replacements), array_values($replacements), $template);
        } elseif ($text === '') {
            $default = Setting::get('invoice_comment_default') ?: __('orders.invoice_attached');
            $text = $default;
        }

        $replacements = [
            '{amount}' => number_format($total, 2),
            '{order_number}' => $order->order_number,
            '{date}' => now()->format('Y-m-d H:i'),
            '{currency}' => 'SAR',
        ];

        return '📄 '.str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
