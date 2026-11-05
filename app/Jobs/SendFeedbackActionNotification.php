<?php

namespace App\Jobs;

use App\Mail\FeedbackActionUpdated;
use App\Models\FeedbackAction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendFeedbackActionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;
    public array $backoff = [5, 30, 90];

    public function __construct(public int $actionId, public int $adminId) {}

    public function handle(): void
    {
        $action = FeedbackAction::with('feedback.business')->find($this->actionId);
        if (! $action || in_array($action->admin_email_status, ['sent', 'logged', 'disabled'], true)) {
            return;
        }

        $business = optional($action->feedback)->business;
        $admin = User::find($this->adminId);
        if (! $business || ! $admin) {
            $this->markFailed($action, new \RuntimeException('Feedback action notification recipient is unavailable.'));
            return;
        }

        if (! $business->feedback_email_alerts) {
            $action->update(['admin_email_status' => 'disabled', 'admin_email_error' => null]);
            return;
        }

        $recipient = $business->notificationRecipient();
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->markFailed($action, new \RuntimeException('No valid admin notification email is configured.'));
            return;
        }

        $action->update(['admin_email_status' => 'sending', 'admin_email_error' => null]);
        try {
            Mail::to($recipient)->send(new FeedbackActionUpdated($action->feedback, $action, $admin));
            $transport = config('mail.mailers.' . config('mail.default') . '.transport');
            $action->update([
                'admin_email_status' => in_array($transport, ['log', 'array'], true) ? 'logged' : 'sent',
                'admin_email_error' => null,
            ]);
        } catch (Throwable $error) {
            $action->update(['admin_email_status' => 'queued']);
            Log::warning('Feedback action admin email attempt failed; the queue will retry it.', [
                'feedback_id' => $action->feedback_id,
                'action_id' => $action->id,
                'error' => $error->getMessage(),
            ]);
            throw $error;
        }
    }

    public function failed(Throwable $error): void
    {
        $action = FeedbackAction::find($this->actionId);
        if ($action) {
            $this->markFailed($action, $error);
        }
    }

    private function markFailed(FeedbackAction $action, Throwable $error): void
    {
        $action->update([
            'admin_email_status' => 'failed',
            'admin_email_error' => 'Admin notification failed after automatic retries. Check notification email and SMTP settings.',
        ]);
        Log::error('Feedback action admin email failed after queue retries.', [
            'feedback_id' => $action->feedback_id,
            'action_id' => $action->id,
            'error' => $error->getMessage(),
        ]);
    }
}
