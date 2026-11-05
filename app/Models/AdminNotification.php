<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'feedback_id',
        'contact_message_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function contactMessage()
    {
        return $this->belongsTo(ContactMessage::class);
    }

    public function url(): string
    {
        return route('dashboard.notifications.open', $this);
    }

    public function summary(): string
    {
        if (! $this->feedback) return \Illuminate\Support\Str::limit($this->message, 160);
        $rating = $this->feedback->ratingAverage();
        return $this->feedback->displayCode() . ' | ' . $this->feedback->customerLabel() . ' | ' .
            ($rating === null ? 'N/A' : $rating . '/10') . ' | ' . $this->feedback->status;
    }

    public function icon(): string
    {
        if ($this->type === 'system') return 'monitor';
        if (! $this->feedback_id) return 'message-square';
        if ($this->feedback && $this->feedback->status !== 'Submitted' && $this->feedback->status !== 'Needs Action') return 'refresh-cw';
        return $this->type === 'warning' ? 'triangle-alert' : 'clipboard-list';
    }

    public function compactDetail(): string
    {
        if (! $this->feedback) return \Illuminate\Support\Str::limit($this->message, 100);
        $rating = $this->feedback->ratingAverage();
        $status = $this->feedback->status;
        $detail = in_array($status, ['Submitted', 'Needs Action'], true)
            ? 'Rating ' . ($rating === null ? 'N/A' : $rating . '/10')
            : $status;
        return $this->feedback->displayCode() . ' · ' . $detail;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
