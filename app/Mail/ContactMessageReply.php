<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply as ContactMessageReplyRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage, public ContactMessageReplyRecord $reply)
    {
    }

    public function build()
    {
        $businessName = optional($this->contactMessage->business)->business_name ?: config('app.name');

        return $this->subject($businessName . ' replied to your CFS message')
            ->view('emails.contact-message-reply')
            ->text('emails.contact-message-reply-text');
    }
}
