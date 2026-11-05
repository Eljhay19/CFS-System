<?php

namespace App\Jobs;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendContactMessageNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 100;
    public int $timeout = 15;
    public array $backoff = [5, 30, 120, 300, 600];

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDay();
    }

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $message = ContactMessage::find($this->messageId);
        if (! $message || in_array($message->email_status, ['sent', 'logged', 'disabled'], true)) return;
        $message->update(['email_status' => 'sending', 'email_error' => null]);

        try {
            Mail::to($message->recipient_email)->send(new ContactMessageReceived($message));
            $logged = in_array(config('mail.mailers.' . config('mail.default') . '.transport'), ['log', 'array'], true);
            $message->update([
                'email_status' => $logged ? 'logged' : 'sent',
                'email_sent_at' => $logged ? null : now(),
                'email_error' => null,
            ]);
        } catch (Throwable $error) {
            $message->update(['email_status' => 'queued']);
            Log::warning('Contact message email attempt failed; the queue will retry it.', [
                'contact_message_id' => $message->id,
                'error' => $error->getMessage(),
            ]);
            throw $error;
        }
    }

    public function failed(Throwable $error): void
    {
        $message = ContactMessage::find($this->messageId);
        if (! $message) return;
        $message->update([
            'email_status' => 'failed',
            'email_error' => 'Notification email failed after automatic retries. Check the admin notification address and SMTP settings.',
        ]);
        Log::error('Contact message email failed after queue retries.', [
            'contact_message_id' => $message->id,
            'error' => $error->getMessage(),
        ]);
    }
}
