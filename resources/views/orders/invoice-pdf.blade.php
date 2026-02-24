<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $invoiceLocale ?? app()->getLocale()) }}" dir="{{ ($isRtl ?? (app()->getLocale() === 'ar')) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('orders.invoice_for', ['number' => $order->order_number]) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #374151;
            line-height: 1.5;
            direction: {{ ($isRtl ?? false) ? 'rtl' : 'ltr' }};
        }
        .page { padding: 40px; }
        .header { border-bottom: 3px solid #f97316; padding-bottom: 20px; margin-bottom: 24px; }
        .header h1 { font-size: 22px; color: #1f2937; font-weight: 700; }
        .header .meta { font-size: 10px; color: #6b7280; margin-top: 8px; }
        .customer { background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 24px; }
        .customer h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 6px; }
        .customer p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; direction: inherit; }
        th {
            padding: 10px 12px;
            background: #f3f4f6;
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        th:first-child { text-align: {{ ($isRtl ?? false) ? 'right' : 'left' }}; }
        th.num { text-align: center; }
        th.amt { text-align: {{ ($isRtl ?? false) ? 'left' : 'right' }}; }
        td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        td:first-child { text-align: {{ ($isRtl ?? false) ? 'right' : 'left' }}; }
        td.num { text-align: center; }
        td.amt { text-align: {{ ($isRtl ?? false) ? 'left' : 'right' }}; direction: ltr; }
        tr:last-child td { border-bottom: none; }
        .total-row { font-weight: 700; font-size: 14px; background: #fef3c7; }
        .total-row td { padding: 14px 12px; border-bottom: 2px solid #f97316; }
        .total-row td:first-child { text-align: {{ ($isRtl ?? false) ? 'right' : 'left' }}; }
        .total-row td.amt { text-align: {{ ($isRtl ?? false) ? 'left' : 'right' }}; direction: ltr; }
        .notes { margin-top: 24px; padding: 16px; background: #f9fafb; border-radius: 8px; font-size: 10px; color: #6b7280; }
        .notes h4 { font-size: 10px; color: #374151; margin-bottom: 6px; }
        .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    @php
        use ArPHP\I18N\Arabic;
        $arabic = ($isRtl ?? false) ? new Arabic() : null;
        $r = ($isRtl ?? false) ? function ($s) use ($arabic) {
            if ($s === null || $s === '') return '';
            $s = (string) $s;
            if (!preg_match('/\p{Arabic}/u', $s)) return $s;
            return $arabic->utf8Glyphs($s, 500, false, true);
        } : fn ($s) => $s ?? '';
    @endphp
    <div class="page">
        <div class="header">
            <h1>{{ $r(__('orders.invoice_for', ['number' => $order->order_number])) }}</h1>
            <div class="meta">{{ $r(__('orders.invoice_date')) }}: {{ now()->format('Y/m/d H:i') }} · {{ $r(__('app.name')) }}</div>
        </div>

        @if ($order->user)
        <div class="customer">
            <h3>{{ $r(__('orders.shipping_address')) }}</h3>
            <p><strong>{{ $r($order->user->name) }}</strong></p>
            <p dir="ltr">{{ $order->user->email }}</p>
            @if ($order->shipping_address_snapshot)
                @php $addr = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : []; @endphp
                @if (!empty($addr['line1']))
                    <p>{{ $r(implode(', ', array_filter([$addr['line1'] ?? '', $addr['city'] ?? '', $addr['state'] ?? '', $addr['postal_code'] ?? '']))) }}</p>
                    @if (!empty($addr['country']))<p>{{ $r($addr['country']) }}</p>@endif
                @endif
            @endif
        </div>
        @endif

        @if ($invoiceType === 'detailed' && count($lines) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $r(__('orders.product')) }}</th>
                    <th class="num">{{ $r(__('orders.qty')) }}</th>
                    <th class="amt">{{ $r(__('orders.unit_price')) }}</th>
                    <th class="amt">{{ $r(__('orders.final')) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $idx => $line)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $r($line['description']) }}</td>
                    <td class="num">{{ $line['qty'] }}</td>
                    <td class="amt">{{ $line['unit_price'] }} {{ $line['currency'] ?? 'SAR' }}</td>
                    <td class="amt">{{ number_format($line['line_total'], 2) }} SAR</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <table>
            <tr class="total-row">
                <td colspan="{{ ($invoiceType === 'detailed' && count($lines) > 0) ? 4 : 1 }}">{{ $r(__('orders.invoice_total')) }}</td>
                <td class="amt">{{ number_format($total, 2) }} SAR</td>
            </tr>
        </table>

        @if (!empty($notes))
        <div class="notes">
            <h4>{{ $r(__('orders.invoice_notes')) }}</h4>
            <p>{{ $r($notes) }}</p>
        </div>
        @endif

        <div class="footer">
            {{ $r(__('app.name')) }} · {{ now()->format('Y/m/d H:i') }}
        </div>
    </div>
</body>
</html>
