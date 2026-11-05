<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update on your CFS feedback</title>
</head>
<body style="margin:0;background:#f3f6fa;color:#10213f;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fa;padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #dce4ee;border-radius:12px;overflow:hidden;">
                <tr><td style="background:#10213f;padding:20px 24px;color:#fff;font-size:20px;font-weight:700;">CFS Customer Feedback</td></tr>
                <tr><td style="padding:24px;">
                    <p style="margin:0 0 12px;color:#455875;">Hello,</p>
                    <h1 style="font-size:22px;line-height:1.3;margin:0 0 14px;color:#10213f;">There is an update on your feedback</h1>
                    <p style="margin:0 0 18px;color:#455875;line-height:1.55;">The team at <strong>{{ $feedback->business->business_name }}</strong> reviewed your feedback. This update reflects its current severity and review status.</p>
                    <p style="display:inline-block;margin:0 0 14px;padding:5px 9px;border-radius:4px;background:#f5f8fc;color:{{ $severityColor }};font-size:12px;font-weight:700;">{{ $feedback->severityLevel() }} concern</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                        <tr><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;color:#60708a;">Feedback ID</td><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;text-align:right;font-weight:700;">{{ $feedback->displayCode() }}</td></tr>
                        <tr><td style="padding:9px 0;color:#60708a;">Current status</td><td style="padding:9px 0;text-align:right;font-weight:700;">{{ $action->status }}</td></tr>
                    </table>
                    <div style="margin-top:18px;padding:14px 16px;background:#f5f8fc;border-left:4px solid {{ $severityColor }};border-radius:4px;">
                        <div style="font-size:12px;font-weight:700;color:#60708a;text-transform:uppercase;">Update from the business</div>
                        <div style="font-size:15px;line-height:1.55;margin-top:6px;white-space:pre-wrap;">{{ $customerFacingSummary }}</div>
                    </div>
                    <p style="margin:20px 0 0;color:#60708a;font-size:12px;line-height:1.5;">You are receiving this email because you requested follow-up when you submitted your feedback.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
