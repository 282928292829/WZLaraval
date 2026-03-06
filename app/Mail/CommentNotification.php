<?php

namespace App\Mail;

use App\Models\OrderComment;
use App\Models\Setting;
use App\Services\EmailTemplateService;
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
        $order = $this->comment->order;
        $locale = $order->user?->locale ?? app()->getLocale();
        $siteName = Setting::get('site_name') ?: config('app.name');

        $replacements = [
            'name' => $order->user?->name ?? '',
            'order_number' => $order->order_number ?? '',
            'comment_body' => $this->comment->body ?? '',
            'order_link' => url('/orders/'.$order->id),
        ];

        $subject = app(EmailTemplateService::class)->getSubject('comment_notification', $locale, $replacements);

        return new Envelope(
            subject: $subject ?? __('orders.notification_email_subject', ['number' => $order->order_number ?? '']),
        );
    }

    public function content(): Content
    {
        $order = $this->comment->order;
        $locale = $order->user?->locale ?? app()->getLocale();

        $replacements = [
            'name' => $order->user?->name ?? '',
            'order_number' => $order->order_number ?? '',
            'comment_body' => $this->comment->body ?? '',
            'order_link' => url('/orders/'.$order->id),
        ];

        $body = app(EmailTemplateService::class)->getBody('comment_notification', $locale, $replacements);

        if ($body !== null) {
            $subj = app(EmailTemplateService::class)->getSubject('comment_notification', $locale, $replacements);

            return new Content(
                view: 'emails.plain-text',
                with: [
                    'body' => $body,
                    'locale' => $locale,
                    'subject' => $subj ?? __('orders.notification_email_subject', ['number' => $order->order_number ?? '']),
                ],
            );
        }

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
