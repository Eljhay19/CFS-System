<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\FeedbackAction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackActionUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feedback $feedback,
        public FeedbackAction $action,
        public User $admin
    ) {
    }

    public function build()
    {
        $subject = $this->feedback->hasCustomerConcern()
            ? 'CFS complaint update: ' . $this->feedback->displayCode() . ' — ' . $this->action->status
            : 'CFS feedback update: ' . $this->feedback->displayCode() . ' — ' . $this->action->status;

        return $this->subject($subject)
            ->view('emails.feedback-action-updated')
            ->text('emails.feedback-action-updated-text');
    }
}
