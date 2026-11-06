<?php

namespace App\Services;

use App\Models\Business;

class FeedbackSnapshot
{
    public function revision(Business $business): string
    {
        return hash('sha256', json_encode([
            'date' => now()->toDateString(),
            'feedback' => $business->feedback()->with('answers:id,feedback_id,question_id,answer,question_text,question_type,field_key,updated_at')->orderBy('id')->get(['id', 'status', 'updated_at', 'email_status']),
            'notifications' => $business->user->notifications()->orderBy('id')->get(['id', 'is_read', 'updated_at']),
            'profile' => [$business->email, $business->notification_email, $business->logo],
        ]));
    }
}
