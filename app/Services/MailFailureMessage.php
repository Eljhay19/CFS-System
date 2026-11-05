<?php

namespace App\Services;

use Throwable;

class MailFailureMessage
{
    public function forUser(Throwable $error): string
    {
        $message = strtolower($error->getMessage());

        if (str_contains($message, 'authenticate') || str_contains($message, 'authentication')) {
            return 'The email provider rejected the SMTP sign-in. Update the mail credentials in .env (for Gmail, use a current App Password), restart Laravel, and try again.';
        }

        if (str_contains($message, 'connect') || str_contains($message, 'timed out') || str_contains($message, 'connection')) {
            return 'CFS could not connect to the email provider. Check the SMTP host, port, encryption, and network connection, then try again.';
        }

        return 'The email provider could not send this message. Check the mail service configuration and try again.';
    }
}
