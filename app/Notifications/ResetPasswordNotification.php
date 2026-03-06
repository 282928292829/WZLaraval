<?php

namespace App\Notifications;

use App\Services\EmailTemplateService;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     * Uses admin-configured template when set; otherwise default Laravel mail.
     */
    public function toMail(mixed $notifiable)
    {
        $token = $this->token;
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $locale = $notifiable->locale ?? app()->getLocale();
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        $replacements = [
            'name' => $notifiable->name ?? '',
            'reset_link' => $url,
            'expire_minutes' => (string) $expireMinutes,
        ];

        $service = app(EmailTemplateService::class);
        $subject = $service->getSubject('password_reset', $locale, $replacements);
        $body = $service->getBody('password_reset', $locale, $replacements);

        if ($subject !== null || $body !== null) {
            return new \App\Mail\PasswordResetMail(
                notifiable: $notifiable,
                resetUrl: $url,
                subject: $subject ?? __('Reset Password'),
                body: $body,
                locale: $locale,
            );
        }

        return (new MailMessage)
            ->subject(__('Reset Password'))
            ->line(__('You are receiving this email because we received a password reset request for your account.'))
            ->action(__('Reset Password'), $url)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => $expireMinutes]))
            ->line(__('If you did not request a password reset, no further action is required.'));
    }
}
