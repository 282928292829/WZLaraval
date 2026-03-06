<x-emails.layout :subject="__('orders.status_change_email_subject', ['number' => $order->order_number])">

    <p class="greeting">{{ __('orders.notification_email_greeting', ['name' => $order->user->name ?? __('orders.notification_email_dear_customer')]) }}</p>

    <p class="intro">
        {{ __('orders.status_change_email_intro', ['number' => $order->order_number]) }}
    </p>

    {{-- Status card --}}
    <div class="card">
        <div class="card-title">{{ __('orders.status_change_email_status') }}</div>
        <div class="info-row">
            <span class="info-label">{{ __('email.order_number') }}</span>
            <span class="info-value">{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('orders.status_change_email_new_status') }}</span>
            <span class="info-value">
                <span class="badge badge-orange">{{ __('order.status.' . $order->status) }}</span>
            </span>
        </div>
    </div>

    {{-- CTA --}}
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/orders/' . $order->id) }}" class="btn">
            {{ __('orders.view_order') }}
        </a>
    </div>

    <hr class="divider">

    <p style="font-size:13px;color:#6b7280;line-height:1.7;">
        {{ __('orders.notification_email_footer') }}
    </p>

</x-emails.layout>
