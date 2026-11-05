<?php

namespace App\Mail;

use App\Models\Feedback;
use Illuminate\Mail\Mailable;

class AdminFeedbackSubmitted extends Mailable
{
    public $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function build()
    {
        $severity = $this->feedback->severityLevel();
        $subject = match ($severity) {
            'High' => 'URGENT: Customer Concern Requires Action',
            'Medium' => 'Review Recommended: Customer Feedback',
            default => 'New Customer Feedback Received',
        };

        return $this->subject($subject)
            ->view('emails.admin-feedback-submitted')
            ->text('emails.admin-feedback-submitted-text');
    }
}
