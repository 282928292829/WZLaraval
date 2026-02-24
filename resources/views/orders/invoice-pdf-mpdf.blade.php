<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $invoiceLocale ?? app()->getLocale()) }}" dir="{{ ($isRtl ?? false) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('orders.invoice_for', ['number' => $order->order_number]) }}</title>
    <style>
        body { font-size: 12px; color: #1f2937; line-height: 1.6; font-family: {{ ($isRtl ?? false) ? 'ibmplexarabic' : 'dejavusans' }}; margin: 0; }
        .page { padding: 45px 50px; }
        .header { padding-bottom: 24px; margin-bottom: 28px; border-bottom: 2px solid #e5e7eb; }
        .header h1 { font-size: 24px; color: #111827; font-weight: bold; margin: 0 0 6px 0; }
        .header .meta { font-size: 11px; color: #6b7280; }
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
        .notes { margin-top: 28px; padding: 18px 20px; background: #f8fafc; font-size: 11px; color: #64748b; }
        .notes h4 { font-size: 11px; color: #334155; margin: 0 0 8px 0; font-weight: bold; }
        .footer { margin-top: 36px; padding-top: 18px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>{{ __('orders.invoice_for', ['number' => $order->order_number]) }}</h1>
            <div class="meta">{{ __('orders.invoice_date') }}: {{ now()->format('Y/m/d H:i') }} · {{ __('app.name') }}</div>
        </div>

        @if ($order->user)
        <div class="customer">
            <h3>{{ __('orders.shipping_address') }}</h3>
            <p><strong>{{ $order->user->name }}</strong></p>
            <p dir="ltr">{{ $order->user->email }}</p>
            @if ($order->shipping_address_snapshot)
                @php $addr = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : []; @endphp
                @if (!empty($addr['line1']))
                    <p>{{ implode(', ', array_filter([$addr['line1'] ?? '', $addr['city'] ?? '', $addr['state'] ?? '', $addr['postal_code'] ?? ''])) }}</p>
                    @if (!empty($addr['country']))<p>{{ $addr['country'] }}</p>@endif
                @endif
            @endif
        </div>
        @endif

        @if ($invoiceType === 'detailed' && count($lines) > 0)
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

        <table>
            <tr class="total-row">
                <td colspan="{{ ($invoiceType === 'detailed' && count($lines) > 0) ? 4 : 1 }}">{{ __('orders.invoice_total') }}</td>
                <td class="col-total amt">{{ number_format($total, 2) }} SAR</td>
            </tr>
        </table>

        @if (!empty($notes))
        <div class="notes">
            <h4>{{ __('orders.invoice_notes') }}</h4>
            <p>{{ $notes }}</p>
        </div>
        @endif

        <div class="footer">
            {{ __('app.name') }} · {{ now()->format('Y/m/d H:i') }}
        </div>
    </div>
</body>
</html>
