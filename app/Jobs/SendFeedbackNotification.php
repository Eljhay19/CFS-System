<?php

namespace App\Jobs;

use App\Models\Feedback;
use App\Services\FeedbackNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendFeedbackNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;
    public array $backoff = [5, 30, 90];

    public function __construct(public int $feedbackId) {}

    public function handle(FeedbackNotifications $notifications): void
    {
        $feedback = Feedback::with('business.user', 'answers.question')->find($this->feedbackId);
        if (! $feedback || in_array($feedback->email_status, ['sent', 'logged', 'disabled'], true)) {
            return;
        }

        try {
            $notifications->sendEmail($feedback, true);
        } catch (Throwable $error) {
            Feedback::whereKey($this->feedbackId)->update([
                'email_status' => 'queued',
                'email_error' => null,
            ]);
            throw $error;
        }
    }

    public function failed(Throwable $error): void
    {
        try {
            Feedback::whereKey($this->feedbackId)->update([
                'email_status' => 'failed',
                'email_error' => 'Email could not be delivered after retrying.',
            ]);
        } catch (Throwable $statusError) {
            report($statusError);
        }

        report($error);
    }
}
