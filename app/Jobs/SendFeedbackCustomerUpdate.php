<?php

namespace App\Jobs;

use App\Models\FeedbackAction;
use App\Services\FeedbackCustomerUpdates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendFeedbackCustomerUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;
    public array $backoff = [5, 30, 90];

    public function __construct(public int $actionId) {}

    public function handle(FeedbackCustomerUpdates $updates): void
    {
        $action = FeedbackAction::find($this->actionId);
        if (! $action || in_array($action->customer_email_status, ['sent', 'not_requested'], true)) {
            return;
        }

        $updates->deliver($action);
    }

    public function failed(Throwable $error): void
    {
        $action = FeedbackAction::with('feedback')->find($this->actionId);
        if ($action && $action->feedback) {
            app(FeedbackCustomerUpdates::class)->markFailed($action->feedback, $action, $error);
        }
    }
}
