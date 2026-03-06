<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusChangeNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        $locale = $this->order->user?->locale ?? app()->getLocale();
        $replacements = [
            'name' => $this->order->user?->name ?? '',
            'order_number' => $this->order->order_number ?? '',
            'new_status' => (string) __('order.status.'.$this->order->status),
            'order_link' => url('/orders/'.$this->order->id),
        ];

        $subject = app(EmailTemplateService::class)->getSubject('status_change', $locale, $replacements);

        return new Envelope(
            subject: $subject ?? __('orders.status_change_email_subject', ['number' => $this->order->order_number ?? '']),
        );
    }

    public function content(): Content
    {
        $locale = $this->order->user?->locale ?? app()->getLocale();
        $replacements = [
            'name' => $this->order->user?->name ?? '',
            'order_number' => $this->order->order_number ?? '',
            'new_status' => (string) __('order.status.'.$this->order->status),
            'order_link' => url('/orders/'.$this->order->id),
        ];

        $body = app(EmailTemplateService::class)->getBody('status_change', $locale, $replacements);

        if ($body !== null) {
            $subj = app(EmailTemplateService::class)->getSubject('status_change', $locale, $replacements);

            return new Content(
                view: 'emails.plain-text',
                with: [
                    'body' => $body,
                    'locale' => $locale,
                    'subject' => $subj ?? __('orders.status_change_email_subject', ['number' => $this->order->order_number ?? '']),
                ],
            );
        }

        return new Content(
            view: 'emails.status-change-notification',
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

        return $user->notify_orders !== false;
    }
}
