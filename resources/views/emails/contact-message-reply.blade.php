<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Response to your message</title></head>
<body style="margin:0;background:#f3f6fa;color:#10213f;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fa;padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #dce4ee;border-radius:12px;overflow:hidden;">
                <tr><td style="background:#10213f;padding:18px 24px;color:#fff;font-size:19px;font-weight:700;">{{ optional($contactMessage->business)->business_name ?: config('app.name') }}</td></tr>
                <tr><td style="padding:24px;">
                    <p style="margin:0 0 12px;color:#455875;">Hello {{ $contactMessage->name ?: 'there' }},</p>
                    <h1 style="font-size:21px;line-height:1.35;margin:0 0 12px;color:#10213f;">A response to your message</h1>
                    <p style="margin:0 0 18px;color:#455875;line-height:1.55;">The team has replied to your Contact Us message.</p>
                    <div style="padding:14px 16px;background:#f5f8fc;border-left:4px solid #ed7a00;border-radius:4px;font-size:15px;line-height:1.6;white-space:pre-wrap;">{{ $reply->body }}</div>
                    <p style="margin:20px 0 0;color:#60708a;font-size:12px;line-height:1.5;">This response is about message #{{ $contactMessage->id }}. You can reply to this email if you need further help.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
