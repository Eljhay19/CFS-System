<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    protected $attributes = ['dashboard_notifications' => true, 'feedback_email_alerts' => true, 'contact_message_alerts' => true, 'survey_enabled' => true, 'use_account_email_for_feedback' => true];

    protected $fillable = [
        'user_id',
        'business_name',
        'owner_name',
        'business_type',
        'address',
        'contact_number',
        'email',
        'logo',
        'logo_data',
        'logo_mime',
        'description',
        'notification_email', 'survey_slug', 'survey_enabled', 'is_default',
        'dashboard_notifications', 'feedback_email_alerts', 'contact_message_alerts', 'closed_survey_message',
        'use_account_email_for_feedback', 'notification_email_verified_at', 'pending_notification_email',
    ];

    protected $casts = ['survey_enabled' => 'boolean', 'is_default' => 'boolean', 'dashboard_notifications' => 'boolean', 'feedback_email_alerts' => 'boolean', 'contact_message_alerts' => 'boolean', 'use_account_email_for_feedback' => 'boolean', 'notification_email_verified_at' => 'datetime'];

    public function surveySteps()
    {
        return $this->hasMany(SurveyStep::class)->orderBy('id');
    }

    public function closedMessage(): string
    {
        return $this->closed_survey_message ?: 'Thanks for visiting. Our survey is currently closed.';
    }

    protected static function booted()
    {
        static::creating(function ($business) {
            $business->survey_slug = $business->survey_slug ?: (string) \Illuminate\Support\Str::uuid();
            $business->notification_email = $business->notification_email ?: $business->email;
            $business->is_default = ! static::exists();
        });
        static::created(function ($business) {
            app(\App\Services\SurveyDefinition::class)->initialize($business);
        });
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function notificationRecipient(): ?string
    {
        if (! $this->use_account_email_for_feedback && $this->notification_email_verified_at && $this->notification_email) {
            return $this->notification_email;
        }

        return optional($this->user)->email ?: $this->email;
    }

    public function hasProfileDetails(): bool
    {
        foreach (['business_name', 'owner_name', 'business_type', 'address', 'contact_number', 'email'] as $field) {
            if (blank($this->$field)) return false;
        }
        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}
