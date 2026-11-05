<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCode extends Notification
{
    public $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->subject('CFS Admin Password Reset Code')
            ->line('This code expires in 5 minutes and can only be used once.')
            ->line('If you did not request this password reset, you can safely ignore this email.')
            ->view('emails.password-reset-code', ['code' => $this->code]);
    }
}
