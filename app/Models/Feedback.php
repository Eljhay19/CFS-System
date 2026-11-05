<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $attributes = [
        'status' => 'Submitted',
        'concern_level' => 'Normal',
    ];

    protected $hidden = ['submission_token', 'follow_up_email', 'follow_up_consent_at'];

    protected $fillable = [
        'feedback_code',
        'submission_token',
        'business_id',
        'submitted_at',
        'status',
        'concern_level',
        'action_taken',
        'email_sent_at',
        'consent_at', 'policy_version', 'email_status', 'email_error', 'resolution_note', 'resolved_at',
        'follow_up_email', 'follow_up_consent_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'consent_at' => 'datetime', 'resolved_at' => 'datetime',
        'follow_up_email' => 'encrypted',
        'follow_up_consent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function (Feedback $feedback) {
            if ($feedback->feedback_code) {
                return;
            }

            static::withoutEvents(function () use ($feedback) {
                $feedback->forceFill([
                    'feedback_code' => $feedback->makeFeedbackCode(),
                ])->save();
            });
        });
    }

    public function makeFeedbackCode()
    {
        return 'FB-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function displayCode()
    {
        return $this->feedback_code ?: $this->makeFeedbackCode();
    }

    public function customerLabel()
    {
        return $this->hasFollowUp() ? 'Follow-up requested' : 'Anonymous';
    }

    public function hasFollowUp(): bool
    {
        return $this->follow_up_email !== null && $this->follow_up_consent_at !== null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function actions()
    {
        return $this->hasMany(FeedbackAction::class);
    }

    public function notification()
    {
        return $this->hasOne(AdminNotification::class)->latestOfMany();
    }

    public function notifications()
    {
        return $this->hasMany(AdminNotification::class);
    }

    public function ratingAverage()
    {
        $ratingAnswers = $this->answers->filter(function ($answer) {
            return $answer->type() === Question::TYPE_RATING && is_numeric($answer->answer);
        });

        if ($ratingAnswers->isEmpty()) {
            return null;
        }

        return round($ratingAnswers->avg(function ($answer) {
            return (int) $answer->answer;
        }), 1);
    }

    public function firstComment()
    {
        $comment = $this->answers->first(function ($answer) {
            $question = strtolower($answer->text());

            return $answer->type() === Question::TYPE_COMMENT
                && $answer->answer
                && ($answer->field_key === 'problem_comment' || strpos($question, 'comment') !== false || strpos($question, 'problem') !== false);
        });

        return optional($comment)->answer;
    }

    public function hasCustomerConcern()
    {
        if (($this->ratingAverage() !== null && $this->ratingAverage() <= 5) || $this->concern_level === 'High') {
            return true;
        }

        return $this->answers->contains(function ($answer) {
            $question = strtolower($answer->text());
            $value = strtolower((string) $answer->answer);

            return ($answer->field_key === 'problem_today' && $value === 'yes')
                || (in_array($answer->field_key, ['order_accurate', 'food_satisfied'], true) && $value === 'like down')
                || (strpos($question, 'problem') !== false && $value === 'yes')
                || (strpos($question, 'accurate') !== false && $value === 'like down')
                || (strpos($question, 'satisfied') !== false && $value === 'like down');
        });
    }

    public function severityLevel(): string
    {
        if ($this->hasCustomerConcern()) {
            return 'High';
        }

        $rating = $this->ratingAverage();

        if ($rating !== null && $rating <= 7) {
            return 'Medium';
        }

        return 'Normal';
    }
}
