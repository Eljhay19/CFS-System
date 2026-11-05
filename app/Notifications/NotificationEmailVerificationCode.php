<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationEmailVerificationCode extends Notification
{
    public function __construct(public string $code) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your CFS feedback notification email')
            ->greeting('Verify your notification email')
            ->line('Enter this verification code in CFS Settings to receive customer feedback alerts at this address:')
            ->line($this->code)
            ->line('This code expires in 5 minutes and can only be used once.')
            ->line('If you did not request this change, you can safely ignore this email.');
    }
}
