<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackAction extends Model
{
    protected $fillable = ['user_id', 'previous_status', 'status', 'note', 'customer_message', 'customer_email_status', 'customer_email_error', 'admin_email_status', 'admin_email_error'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }
}
