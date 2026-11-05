<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['business_id', 'submission_token', 'name', 'email', 'message', 'recipient_email', 'email_status', 'email_error', 'email_sent_at'];
    protected $hidden = ['submission_token'];
    protected $casts = ['email_sent_at'=>'datetime'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function replies()
    {
        return $this->hasMany(ContactMessageReply::class)->with('user')->latest();
    }
}
