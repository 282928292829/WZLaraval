<x-emails.layout :subject="__('orders.notification_email_subject', ['number' => $comment->order->order_number])">

    <p class="greeting">{{ __('orders.notification_email_greeting', ['name' => $comment->order->user->name ?? __('orders.notification_email_dear_customer')]) }}</p>

    <p class="intro">
        {{ __('orders.notification_email_intro', ['number' => $comment->order->order_number]) }}
    </p>

    {{-- Comment card --}}
    <div class="card">
        <div class="card-title">{{ __('orders.notification_email_message') }}</div>
        <div style="font-size:14px;color:#374151;line-height:1.8;white-space:pre-wrap;">{{ $comment->body }}</div>
    </div>

    {{-- CTA --}}
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ url('/orders/' . $comment->order->id) }}" class="btn">
            {{ __('orders.view_order') }}
        </a>
    </div>

    <hr class="divider">

    <p style="font-size:13px;color:#6b7280;line-height:1.7;">
        {{ __('orders.notification_email_footer') }}
    </p>

</x-emails.layout>
