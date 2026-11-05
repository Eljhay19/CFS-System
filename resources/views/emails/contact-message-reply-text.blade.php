{{ optional($contactMessage->business)->business_name ?: config('app.name') }}

Hello {{ $contactMessage->name ?: 'there' }},

The team has replied to your Contact Us message (#{{ $contactMessage->id }}):

{{ $reply->body }}

You can reply to this email if you need further help.
