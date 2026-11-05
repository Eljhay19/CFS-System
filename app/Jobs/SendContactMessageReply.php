<?php

namespace App\Jobs;

use App\Mail\ContactMessageReply as ContactMessageReplyMail;
use App\Models\ContactMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendContactMessageReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;
    public array $backoff = [5, 30, 90];

    public function __construct(public int $replyId) {}

    public function handle(): void
    {
        $reply = ContactMessageReply::with('contactMessage.business')->find($this->replyId);
        if (! $reply || in_array($reply->email_status, ['sent', 'logged'], true)) return;
        $message = $reply->contactMessage;
        if (! $message) return;
        $reply->update(['email_status' => 'sending', 'email_error' => null]);

        try {
            Mail::to($message->email)->send(new ContactMessageReplyMail($message, $reply));
            $logged = in_array(config('mail.mailers.' . config('mail.default') . '.transport'), ['log', 'array'], true);
            $reply->update([
                'email_status' => $logged ? 'logged' : 'sent',
                'email_sent_at' => $logged ? null : now(),
                'email_error' => null,
            ]);
        } catch (Throwable $error) {
            $reply->update(['email_status' => 'queued']);
            Log::warning('Contact reply email attempt failed; the queue will retry it.', [
                'contact_message_id' => $message->id,
                'reply_id' => $reply->id,
                'error' => $error->getMessage(),
            ]);
            throw $error;
        }
    }

    public function failed(Throwable $error): void
    {
        $reply = ContactMessageReply::with('contactMessage')->find($this->replyId);
        if (! $reply) return;
        $reply->update([
            'email_status' => 'failed',
            'email_error' => 'Reply email failed after automatic retries. Check SMTP settings before retrying.',
        ]);
        Log::error('Contact reply email failed after queue retries.', [
            'contact_message_id' => optional($reply->contactMessage)->id,
            'reply_id' => $reply->id,
            'error' => $error->getMessage(),
        ]);
    }
}
