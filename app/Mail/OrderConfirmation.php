<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تأكيد الطلب #{$this->order->order_number} — Wasetzon",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }

    /**
     * Only send if the user has not unsubscribed and has order notifications enabled.
     */
    public function shouldSend(): bool
    {
        $user = $this->order->user;

        if (! $user) {
            return false;
        }

        if ($user->unsubscribed_all) {
            return false;
        }

        // Default true — notify_orders defaults to true for new users
        return $user->notify_orders !== false;
    }
}
