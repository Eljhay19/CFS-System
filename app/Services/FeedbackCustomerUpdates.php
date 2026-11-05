<?php

namespace App\Services;

use App\Mail\FeedbackCustomerUpdate;
use App\Jobs\SendFeedbackCustomerUpdate;
use App\Models\Feedback;
use App\Models\FeedbackAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeedbackCustomerUpdates
{
    public function queue(Feedback $feedback, FeedbackAction $action): string
    {
        $hasCustomerFacingUpdate = $action->previous_status !== $action->status || filled($action->customer_message);

        if (! $feedback->hasFollowUp() || ! $hasCustomerFacingUpdate) {
            $action->update(['customer_email_status' => 'not_requested', 'customer_email_error' => null]);
            return 'not_requested';
        }

        if (in_array($action->customer_email_status, ['sent', 'logged'], true)) {
            return $action->customer_email_status;
        }

        if (in_array($action->customer_email_status, ['queued', 'sending'], true)) {
            return $action->customer_email_status;
        }

        try {
            $action->update(['customer_email_status' => 'queued', 'customer_email_error' => null]);
            SendFeedbackCustomerUpdate::dispatch($action->id)->afterCommit();

            return $action->fresh()->customer_email_status;
        } catch (Throwable $error) {
            $this->markFailed($feedback, $action, $error);

            return 'failed';
        }
    }

    public function deliver(FeedbackAction $action): void
    {
        $feedback = $action->feedback()->with('business')->first();
        if (! $feedback || ! $feedback->hasFollowUp() || ! filled($action->customer_message)) {
            $action->update(['customer_email_status' => 'not_requested', 'customer_email_error' => null]);
            return;
        }

        if (in_array($action->customer_email_status, ['sent', 'logged'], true)) {
            return;
        }

        $action->update(['customer_email_status' => 'sending', 'customer_email_error' => null]);

        try {
            Mail::to($feedback->follow_up_email)->send(new FeedbackCustomerUpdate($feedback, $action));
            $transport = config('mail.mailers.' . config('mail.default') . '.transport');
            $action->update([
                'customer_email_status' => in_array($transport, ['log', 'array'], true) ? 'logged' : 'sent',
                'customer_email_error' => null,
            ]);
        } catch (Throwable $error) {
            $action->update(['customer_email_status' => 'queued']);
            Log::warning('Customer feedback update email attempt failed; the queue will retry it.', [
                'feedback_id' => $feedback->id,
                'action_id' => $action->id,
                'error' => $error->getMessage(),
            ]);

            throw $error;
        }
    }

    public function markFailed(Feedback $feedback, FeedbackAction $action, Throwable $error): void
    {
        Log::error('Customer feedback update email failed after queue retries.', [
            'feedback_id' => $feedback->id,
            'action_id' => $action->id,
            'error' => $error->getMessage(),
        ]);

        try {
            $action->update([
                'customer_email_status' => 'failed',
                'customer_email_error' => 'Email delivery failed after automatic retries. Check SMTP settings before retrying.',
            ]);
        } catch (Throwable $statusError) {
            report($statusError);
        }
    }
}
