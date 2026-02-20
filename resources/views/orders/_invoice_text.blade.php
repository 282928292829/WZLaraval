@php
    $notes = $validated['custom_notes'] ?? '';
    $type  = $validated['invoice_type'] ?? 'detailed';
@endphp
━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 {{ __('orders.invoice_for', ['number' => $order->order_number]) }}
{{ __('orders.invoice_date') }}: {{ now()->format('Y/m/d H:i') }}
━━━━━━━━━━━━━━━━━━━━━━━━━━
@if ($type === 'detailed' && count($lines))

📦 {{ __('orders.invoice_items') }}:
@foreach ($lines as $line)
{{ $line }}
@endforeach
━━━━━━━━━━━━━━━━━━━━━━━━━━
@endif
💰 {{ __('orders.invoice_total') }}: {{ number_format($total, 2) }} SAR
@if ($notes)

📝 {{ __('orders.invoice_notes') }}:
{{ $notes }}
@endif

━━━━━━━━━━━━━━━━━━━━━━━━━━
