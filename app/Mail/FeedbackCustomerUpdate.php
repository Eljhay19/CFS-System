<?php

namespace App\Mail;

use App\Models\Feedback;
use App\Models\FeedbackAction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackCustomerUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerFacingSummary;
    public string $severityColor;

    public function __construct(public Feedback $feedback, public FeedbackAction $action)
    {
        $this->customerFacingSummary = $this->buildCustomerFacingSummary();
        $this->severityColor = $this->severityColor();
    }

    public function build()
    {
        $severity = $this->feedback->severityLevel();
        $subjectPrefix = match ($severity) {
            'High' => 'Priority CFS complaint update',
            'Medium' => 'CFS feedback review update',
            default => 'CFS feedback update',
        };

        return $this->subject($subjectPrefix . ' ' . $this->feedback->displayCode() . ' — ' . $this->action->status)
            ->view('emails.feedback-customer-update')
            ->text('emails.feedback-customer-update-text');
    }

    private function buildCustomerFacingSummary(): string
    {
        if (filled($this->action->customer_message)) {
            return $this->action->customer_message;
        }

        $severity = $this->feedback->severityLevel();

        if ($severity === 'High') {
            return match ($this->action->status) {
                'Needs Action' => 'We are sorry to hear about this serious concern. The business has flagged it for priority review. The team will send you another update after reviewing it.',
                'In Progress' => 'The business is actively reviewing your concern. The status above reflects that work is underway.',
                'Resolved' => 'The business has marked your concern as resolved and recorded its action. If the outcome does not address your concern, please contact the business using its published contact details.',
                default => 'The business has recorded an update about your concern. The status above shows its current review stage.',
            };
        }

        if ($severity === 'Medium') {
            return match ($this->action->status) {
                'In Progress' => 'Thank you for sharing your feedback. The business is reviewing it, and the status above shows that follow-up is underway.',
                'Resolved' => 'Thank you for your feedback. The business has reviewed it and marked its follow-up as resolved.',
                default => 'Thank you for your feedback. The business has reviewed it and recorded the latest status above.',
            };
        }

        return $this->action->status === 'Resolved'
            ? 'Thank you for your feedback. The business has reviewed it and marked its follow-up as resolved.'
            : 'Thank you for helping the business improve. It has reviewed your feedback and recorded the latest status above.';
    }

    public function severityColor(): string
    {
        return match ($this->feedback->severityLevel()) {
            'High' => '#c62828',
            'Medium' => '#b45309',
            default => '#1f5fae',
        };
    }
}
