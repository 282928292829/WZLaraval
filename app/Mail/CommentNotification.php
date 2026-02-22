<?php

namespace App\Mail;

use App\Models\OrderComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly OrderComment $comment) {}

    public function envelope(): Envelope
    {
        $orderNumber = $this->comment->order->order_number ?? '';

        return new Envelope(
            subject: __('orders.notification_email_subject', ['number' => $orderNumber]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comment-notification',
        );
    }

    public function shouldSend(): bool
    {
        $user = $this->comment->order?->user;

        if (! $user) {
            return false;
        }

        if ($user->unsubscribed_all) {
            return false;
        }

        return $user->notify_orders !== false;
    }
}
