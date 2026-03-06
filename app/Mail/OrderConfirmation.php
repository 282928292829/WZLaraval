<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use App\Services\EmailTemplateService;
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
        $siteName = Setting::get('site_name') ?: config('app.name');
        $locale = $this->order->user?->locale ?? app()->getLocale();

        $replacements = [
            'name' => $this->order->user?->name ?? '',
            'site_name' => $siteName,
            'order_number' => $this->order->order_number ?? '',
            'order_link' => url('/orders/'.$this->order->id),
        ];

        $subject = app(EmailTemplateService::class)->getSubject('order_confirmation', $locale, $replacements);

        return new Envelope(
            subject: $subject ?? __('orders.order_confirmation_email_subject', [
                'number' => $this->order->order_number,
                'site_name' => $siteName,
            ]),
        );
    }

    public function content(): Content
    {
        $siteName = Setting::get('site_name') ?: config('app.name');
        $locale = $this->order->user?->locale ?? app()->getLocale();

        $replacements = [
            'name' => $this->order->user?->name ?? '',
            'site_name' => $siteName,
            'order_number' => $this->order->order_number ?? '',
            'order_link' => url('/orders/'.$this->order->id),
        ];

        $body = app(EmailTemplateService::class)->getBody('order_confirmation', $locale, $replacements);

        if ($body !== null) {
            $subj = app(EmailTemplateService::class)->getSubject('order_confirmation', $locale, $replacements);

            return new Content(
                view: 'emails.plain-text',
                with: [
                    'body' => $body,
                    'locale' => $locale,
                    'subject' => $subj ?? __('orders.order_confirmation_email_subject', [
                        'number' => $this->order->order_number,
                        'site_name' => $siteName,
                    ]),
                ],
            );
        }

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
