<?php

namespace App\Services;

use App\Mail\AdminFeedbackSubmitted;
use App\Models\Feedback;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeedbackNotifications
{
    public function record(Feedback $feedback): void
    {
        if (! $feedback->business->dashboard_notifications) return;
        $severity = $feedback->severityLevel();
        $isConcern = $severity === 'High';
        $rating = $feedback->ratingAverage();

        $feedback->business->user->notifications()->create([
            'feedback_id' => $feedback->id,
            'title' => match ($severity) {
                'High' => 'High concern needs action',
                'Medium' => 'Customer feedback needs review',
                default => 'New customer feedback received',
            },
            'message' => 'Feedback ID: ' . $feedback->displayCode() . '. Customer: ' . $feedback->customerLabel() . '. ' .
                'Concern level: ' . $severity . '. Average rating: ' . ($rating === null ? 'N/A' : $rating . '/10') . '. ' .
                'Status: ' . $feedback->status . '. Comment: ' . ($feedback->firstComment() ?: 'No comment provided.'),
            'type' => $isConcern || $severity === 'Medium' ? 'warning' : 'info',
        ]);
    }

    public function sendEmail(Feedback $feedback, bool $throwOnFailure = false): void
    {
        if (! $feedback->business->feedback_email_alerts) {
            try { $feedback->update(['email_status' => 'disabled', 'email_error' => null]); }
            catch (Throwable $error) { report($error); }
            return;
        }
        $email = $feedback->business->notificationRecipient();
        if (! $email) {
            $feedback->update(['email_status' => 'failed', 'email_error' => 'No notification recipient is configured.']);
            Log::warning('Feedback notification has no recipient', ['feedback_id' => $feedback->id]);
            return;
        }

        try {
            Mail::to($email)->send(new AdminFeedbackSubmitted($feedback));
            // Log/array transports do not deliver email to an inbox.
            $transport = config('mail.mailers.' . config('mail.default') . '.transport');
            if (! in_array($transport, ['log', 'array'], true)) {
                $feedback->update(['email_sent_at' => now(), 'email_status' => 'sent', 'email_error' => null]);
            } else {
                $feedback->update(['email_status' => 'logged', 'email_error' => null]);
            }
        } catch (Throwable $error) {
            try {
                $feedback->update(['email_status' => 'failed', 'email_error' => 'Email could not be delivered to the mail provider.']);
            } catch (Throwable $statusError) { /* Saved feedback must survive mail/status failures. */ }
            Log::error('Feedback email notification failed', [
                'feedback_id' => $feedback->id,
                'error' => $error->getMessage(),
            ]);
            if ($throwOnFailure) throw $error;
        }
    }
}
