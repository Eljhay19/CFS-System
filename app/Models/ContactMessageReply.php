<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessageReply extends Model
{
    protected $fillable = ['contact_message_id', 'user_id', 'body', 'email_status', 'email_error', 'email_sent_at'];

    protected $casts = ['email_sent_at' => 'datetime'];

    public function contactMessage()
    {
        return $this->belongsTo(ContactMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
