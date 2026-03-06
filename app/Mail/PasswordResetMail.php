<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object $notifiable,
        public readonly string $resetUrl,
        public readonly string $subject,
        public readonly ?string $body,
        public readonly string $locale = 'ar',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        $body = $this->body;
        if ($body === null || $body === '') {
            $body = __('You are receiving this email because we received a password reset request for your account.')
                ."\n\n"
                .__('Reset Password').': '.$this->resetUrl;
        }

        return new Content(
            view: 'emails.plain-text',
            with: [
                'body' => $body,
                'locale' => $this->locale,
                'subject' => $this->subject,
            ],
        );
    }
}
