<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use App\Services\EmailTemplateService;
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
        $siteName = Setting::get('site_name') ?: config('app.name');
        $locale = $this->user->locale ?? app()->getLocale();

        $replacements = [
            'name' => $this->user->name ?? '',
            'site_name' => $siteName,
            'account_email' => $this->user->email ?? '',
            'joined_date' => $this->user->created_at?->format('Y/m/d') ?? '',
            'cta_link' => url('/new-order'),
        ];

        $subject = app(EmailTemplateService::class)->getSubject('welcome', $locale, $replacements);

        return new Envelope(
            subject: $subject ?? __('email.welcome.subject', ['site_name' => $siteName]),
        );
    }

    public function content(): Content
    {
        $siteName = Setting::get('site_name') ?: config('app.name');
        $locale = $this->user->locale ?? app()->getLocale();

        $replacements = [
            'name' => $this->user->name ?? '',
            'site_name' => $siteName,
            'account_email' => $this->user->email ?? '',
            'joined_date' => $this->user->created_at?->format('Y/m/d') ?? '',
            'cta_link' => url('/new-order'),
        ];

        $body = app(EmailTemplateService::class)->getBody('welcome', $locale, $replacements);

        if ($body !== null) {
            $subj = app(EmailTemplateService::class)->getSubject('welcome', $locale, $replacements);

            return new Content(
                view: 'emails.plain-text',
                with: [
                    'body' => $body,
                    'locale' => $locale,
                    'subject' => $subj ?? __('email.welcome.subject', ['site_name' => $siteName]),
                ],
            );
        }

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
