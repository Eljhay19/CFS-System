<?php

namespace App\Services;

use App\Jobs\SendFeedbackActionNotification;
use App\Models\Feedback;
use App\Models\FeedbackAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class FeedbackActionNotifications
{
    public function notify(Feedback $feedback, FeedbackAction $action, User $admin): string
    {
        $business = $feedback->business;
        $rating = $feedback->ratingAverage();
        $concern = $feedback->hasCustomerConcern();
        $title = $concern
            ? ($action->status === 'Resolved' ? 'Customer complaint resolved' : 'Customer complaint action updated')
            : 'Feedback status updated';
        $message = sprintf(
            '%s | %s | Rating: %s | Status: %s | Admin note: %s',
            $feedback->displayCode(),
            $feedback->firstComment() ?: 'No customer comment provided.',
            $rating === null ? 'N/A' : $rating . '/10',
            $action->status,
            $action->note ?: 'No action note provided.'
        );

        if ($business->dashboard_notifications) {
            try {
                $admin->notifications()->create([
                    'feedback_id' => $feedback->id,
                    'title' => $title,
                    'message' => $message,
                    'type' => $action->status === 'Resolved' ? 'success' : ($concern ? 'warning' : 'info'),
                    'is_read' => false,
                ]);
            } catch (Throwable $error) {
                Log::error('Could not create feedback action notification', [
                    'feedback_id' => $feedback->id,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        if (! $business->feedback_email_alerts) {
            $action->update(['admin_email_status' => 'disabled', 'admin_email_error' => null]);
            return 'disabled';
        }

        $recipient = $business->notificationRecipient();
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $action->update([
                'admin_email_status' => 'failed',
                'admin_email_error' => 'No valid admin notification email is configured.',
            ]);
            return 'failed';
        }

        try {
            $action->update(['admin_email_status' => 'queued', 'admin_email_error' => null]);
            SendFeedbackActionNotification::dispatch($action->id, $admin->id)->afterCommit();
            return $action->fresh()->admin_email_status;
        } catch (Throwable $error) {
            $action->update([
                'admin_email_status' => 'failed',
                'admin_email_error' => 'Admin notification could not be queued. Check the database queue settings.',
            ]);
            Log::error('Feedback action admin email could not be queued.', [
                'feedback_id' => $feedback->id,
                'error' => $error->getMessage(),
            ]);
            return 'failed';
        }
    }
}
