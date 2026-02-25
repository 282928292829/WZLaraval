@php
    $settings = $settings ?? [];
    $extra = $extra ?? [];
    $siteName = $settings['site_name'] ?? config('app.name');
    $logoPath = $settings['logo_path'] ?? null;
    $showOrderNumber = $settings['show_order_number'] ?? true;
    $showCustomerName = $settings['show_customer_name'] ?? true;
    $showEmail = $settings['show_email'] ?? true;
    $showPhone = $settings['show_phone'] ?? true;
    $showItemsTable = in_array($invoiceType, ['items_cost', 'detailed'], true) && count($lines) > 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $invoiceLocale ?? app()->getLocale()) }}" dir="{{ ($isRtl ?? false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $showOrderNumber ? __('orders.invoice_for', ['number' => $order->order_number]) : __('orders.invoice') }}</title>
    <style>
        body { font-size: 12px; color: #1f2937; line-height: 1.6; font-family: {{ ($isRtl ?? false) ? 'ibmplexarabic' : 'dejavusans' }}; margin: 0; }
        .page { padding: 45px 50px; }
        .header { padding-bottom: 24px; margin-bottom: 28px; border-bottom: 2px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .header-left { flex: 1; min-width: 0; }
        .header h1 { font-size: 24px; color: #111827; font-weight: bold; margin: 0 0 6px 0; }
        .header .meta { font-size: 11px; color: #6b7280; }
        .header img { max-height: 60px; max-width: 180px; object-fit: contain; }
        .greeting { font-size: 16px; color: #111827; margin-bottom: 16px; font-weight: bold; }
        .customer { background: #f8fafc; padding: 18px 20px; margin-bottom: 28px; }
        .customer h3 { font-size: 11px; color: #64748b; margin: 0 0 8px 0; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .customer p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { padding: 12px 14px; background: #f1f5f9; font-size: 11px; color: #475569; font-weight: bold; }
        td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        .col-num { width: 8%; text-align: center; }
        .col-qty { width: 10%; text-align: center; }
        .col-price { width: 18%; text-align: right; direction: ltr; }
        .col-total { width: 18%; text-align: right; direction: ltr; }
        .col-desc { }
        .amt { text-align: right; direction: ltr; }
        .total-row { font-weight: bold; font-size: 15px; background: #fefce8; }
        .total-row td { padding: 16px 14px; border: none; border-top: 2px solid #eab308; }
        .summary-row td { padding: 10px 14px; }
        .notes { margin-top: 28px; padding: 18px 20px; background: #f8fafc; font-size: 11px; color: #64748b; }
        .notes h4 { font-size: 11px; color: #334155; margin: 0 0 8px 0; font-weight: bold; }
        .footer { margin-top: 36px; padding-top: 18px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; }
        .shipping-info { margin-bottom: 20px; padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; }
        .shipping-info p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="header-left">
                @if ($logoPath && is_file($logoPath))
                    <img src="{{ $logoPath }}" alt="{{ $siteName }}">
                @else
                    <h1>{{ $siteName }}</h1>
                @endif
                <div class="meta">{{ __('orders.invoice_date') }}: {{ now()->format('Y/m/d H:i') }} · {{ $siteName }}</div>
            </div>
        </div>

        @if ($invoiceType === 'second_final' && (!empty($extra['weight']) || !empty($extra['shipping_company'])))
        <div class="shipping-info">
            <p><strong>{{ __('orders.invoice_order_ready') }}</strong></p>
            @if (!empty($extra['weight']))
                <p>{{ __('orders.invoice_weight') }}: {{ $extra['weight'] }}</p>
            @endif
            @if (!empty($extra['shipping_company']))
                @php
                    $invoiceCarrier = \App\Models\ShippingCompany::where('slug', $extra['shipping_company'])->first();
                    $invoiceCarrierLabel = $invoiceCarrier?->display_name ?? ($extra['shipping_company'] === 'other' ? __('orders.carrier_other') : $extra['shipping_company']);
                @endphp
                <p>{{ __('orders.invoice_shipping_company') }}: {{ $invoiceCarrierLabel }}</p>
            @endif
        </div>
        @endif

        @if ($showOrderNumber)
        <p class="greeting">{{ __('orders.invoice_for', ['number' => $order->order_number]) }}</p>
        @endif

        @if ($order->user && ($showCustomerName || $showEmail || $showPhone))
        <div class="customer">
            <h3>{{ __('orders.shipping_address') }}</h3>
            @if ($showCustomerName)
                <p><strong>{{ $order->user->name }}</strong></p>
            @endif
            @if ($showEmail)
                <p dir="ltr">{{ $order->user->email }}</p>
            @endif
            @if ($showPhone && !empty($order->user->phone))
                <p dir="ltr">{{ $order->user->phone }}</p>
            @endif
            @if ($order->shipping_address_snapshot)
                @php $addr = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : []; @endphp
                @if (!empty($addr['line1']))
                    <p>{{ implode(', ', array_filter([$addr['line1'] ?? '', $addr['city'] ?? '', $addr['state'] ?? '', $addr['postal_code'] ?? ''])) }}</p>
                    @if (!empty($addr['country']))<p>{{ $addr['country'] }}</p>@endif
                @endif
            @endif
        </div>
        @endif

        @if ($showItemsTable)
        <table>
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-desc">{{ __('orders.product') }}</th>
                    <th class="col-qty">{{ __('orders.qty') }}</th>
                    <th class="col-price">{{ __('orders.unit_price') }}</th>
                    <th class="col-total">{{ __('orders.final') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $idx => $line)
                <tr>
                    <td class="col-num">{{ $idx + 1 }}</td>
                    <td class="col-desc">{{ $line['description'] }}</td>
                    <td class="col-qty">{{ $line['qty'] ?? 1 }}</td>
                    <td class="col-price amt">
                        @if(!empty($line['show_original']))
                            {{ number_format($line['unit_price_original'] ?? 0, 2) }} {{ $line['currency'] ?? '' }} / {{ $line['unit_price'] }} SAR
                        @else
                            {{ $line['unit_price'] }} SAR
                        @endif
                    </td>
                    <td class="col-total amt">{{ number_format($line['line_total'], 2) }} SAR</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if (in_array($invoiceType, ['first_payment', 'general']) && count($lines) > 0 && !$showItemsTable)
        <table>
            <thead>
                <tr>
                    <th class="col-desc">{{ __('orders.description') }}</th>
                    <th class="col-total">{{ __('orders.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                <tr>
                    <td class="col-desc">{{ $line['description'] }}</td>
                    <td class="col-total amt">{{ number_format($line['line_total'], 2) }} SAR</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if ($invoiceType === 'second_final' && count($lines) > 0)
        <table>
            <tbody>
                @foreach ($lines as $line)
                <tr class="summary-row">
                    <td class="col-desc">{{ $line['description'] }}</td>
                    <td class="col-total amt">{{ number_format($line['line_total'], 2) }} SAR</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @php
            $totalColspan = $showItemsTable ? 4 : 1;
        @endphp
        <table>
            <tr class="total-row">
                <td colspan="{{ $totalColspan }}">{{ __('orders.invoice_total') }}</td>
                <td class="col-total amt">{{ number_format($total, 2) }} SAR</td>
            </tr>
            @if ($invoiceType === 'second_final' && (($extra['first_payment'] ?? 0) > 0 || ($extra['remaining'] ?? 0) > 0))
            <tr class="summary-row">
                <td colspan="{{ $totalColspan }}">{{ __('orders.invoice_first_payment') }}</td>
                <td class="col-total amt">{{ number_format($extra['first_payment'] ?? 0, 2) }} SAR</td>
            </tr>
            <tr class="summary-row">
                <td colspan="{{ $totalColspan }}">{{ __('orders.invoice_remaining') }}</td>
                <td class="col-total amt">{{ number_format($extra['remaining'] ?? 0, 2) }} SAR</td>
            </tr>
            @endif
        </table>

        @if (!empty($notes))
        <div class="notes">
            <h4>{{ __('orders.invoice_notes') }}</h4>
            <p>{{ $notes }}</p>
        </div>
        @endif

        <div class="footer">
            {{ $siteName }} · {{ now()->format('Y/m/d H:i') }}
        </div>
    </div>
</body>
</html>
