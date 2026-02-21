<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationWelcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'مرحباً بك في واسطزون! — Wasetzon',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-welcome',
        );
    }

    /**
     * Only send if the user has not unsubscribed.
     */
    public function shouldSend(): bool
    {
        return ! $this->user->unsubscribed_all;
    }
}
