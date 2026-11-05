<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountEmailChangeCode extends Notification
{
    public function __construct(public string $code) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your CFS admin account email')
            ->greeting('Verify your new account email')
            ->line('Enter this code to confirm the new email address for your CFS admin account:')
            ->line($this->code)
            ->line('This code expires in 5 minutes and can only be used once.')
            ->line('If you did not request this change, secure your account and contact your system administrator.');
    }
}
