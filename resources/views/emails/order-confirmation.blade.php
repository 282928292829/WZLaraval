@php
    $siteName = $site_name ?? \App\Models\Setting::get('site_name') ?? config('app.name');
@endphp
<x-emails.layout :subject="__('orders.order_confirmation_email_subject', ['number' => $order->order_number, 'site_name' => $siteName])">

    <p class="greeting">{{ __('email.order_greeting') }} {{ $order->user->name ?? __('email.greeting_fallback') }}{{ __('email.comma') }}</p>

    <p class="intro">
        {{ __('email.order_intro', ['site_name' => $siteName]) }}
    </p>

    {{-- Order summary card --}}
    <div class="card">
        <div class="card-title">{{ __('email.order_details') }}</div>
        <div class="info-row">
            <span class="info-label">{{ __('email.order_number') }}</span>
            <span class="info-value">{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('email.order_date') }}</span>
            <span class="info-value">{{ $order->created_at->format('Y/m/d H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('email.status') }}</span>
            <span class="info-value">
                <span class="badge badge-orange">{{ __('order.status.' . $order->status) }}</span>
            </span>
        </div>
        @if($order->total_amount)
        <div class="info-row">
            <span class="info-label">{{ __('email.total_amount') }}</span>
            <span class="info-value">{{ number_format($order->total_amount, 2) }} {{ $order->currency ?? 'SAR' }}</span>
        </div>
        @endif
    </div>

    {{-- Items table --}}
    @if($order->items->count())
    <div class="card">
        <div class="card-title">{{ __('email.products') }}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('email.product') }}</th>
                    <th>{{ __('email.quantity') }}</th>
                    <th>{{ __('email.price') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $i => $item)
                <tr>
                    <td style="color:#9ca3af;font-size:12px;">{{ $i + 1 }}</td>
                    <td>
                        @if($item->url)
                            <a href="{{ $item->url }}" style="color:#f97316;text-decoration:none;font-size:12px;word-break:break-all;">{{ $item->url }}</a>
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                        @if($item->notes)
                            <div style="font-size:11px;color:#6b7280;margin-top:4px;">{{ $item->notes }}</div>
                        @endif
                    </td>
                    <td>{{ $item->qty ?? 1 }}</td>
                    <td>
                        @if($item->price)
                            {{ number_format($item->price, 2) }} {{ $item->currency ?? '' }}
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- CTA --}}
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/orders/' . $order->id) }}" class="btn">
            {{ __('email.view_order') }}
        </a>
    </div>

    <hr class="divider">

    <p style="font-size:13px;color:#6b7280;line-height:1.7;">
        {{ __('email.footer') }}
    </p>

</x-emails.layout>
