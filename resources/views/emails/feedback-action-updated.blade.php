<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>CFS feedback update</title></head>
<body style="margin:0;background:#f2f5f9;color:#10213f;font-family:Arial,Helvetica,sans-serif;">
@php
    $concern = $feedback->hasCustomerConcern();
    $resolved = $action->status === 'Resolved';
    $accent = $resolved ? '#138a5b' : ($concern ? '#c62828' : '#e8750b');
    $rating = $feedback->ratingAverage();
    $business = $feedback->business;
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:26px 12px;background:#f2f5f9"><tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fff;border:1px solid #dce4ee;border-radius:12px;overflow:hidden">
<tr><td style="padding:20px 26px;background:#10213f;color:#fff"><div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#cbd8ec">Customer Feedback System</div><div style="font-size:21px;font-weight:700;margin-top:5px">CFS {{ $concern ? 'Complaint' : 'Feedback' }} Update</div></td></tr>
<tr><td style="padding:24px 26px 10px"><div style="font-size:13px;color:#60708a">Hello {{ $business->owner_name ?: 'Administrator' }},</div><h1 style="font-size:22px;line-height:1.3;margin:8px 0;color:#10213f">{{ $resolved ? 'The customer concern has been resolved' : 'An admin action was recorded' }}</h1><p style="margin:0;color:#455875;line-height:1.55">An update was recorded for feedback <strong>{{ $feedback->displayCode() }}</strong>. The dashboard alert and this email refer to the same customer response.</p></td></tr>
<tr><td style="padding:12px 26px"><div style="padding:13px 15px;border-left:4px solid {{ $accent }};background:{{ $resolved ? '#ecfdf3' : ($concern ? '#fff1f0' : '#fff7ed') }};border-radius:5px"><div style="font-size:12px;color:#60708a;text-transform:uppercase;font-weight:700">{{ $concern ? 'Customer concern' : 'Feedback status' }}</div><div style="font-size:19px;font-weight:700;color:{{ $accent }};margin-top:5px">{{ $action->previous_status }} → {{ $action->status }}</div></div></td></tr>
<tr><td style="padding:8px 26px 20px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px">
<tr><td style="padding:9px 0;border-bottom:1px solid #e8edf3;color:#60708a">Business</td><td style="padding:9px 0;border-bottom:1px solid #e8edf3;text-align:right;font-weight:700">{{ $business->business_name }}</td></tr>
<tr><td style="padding:9px 0;border-bottom:1px solid #e8edf3;color:#60708a">Customer rating</td><td style="padding:9px 0;border-bottom:1px solid #e8edf3;text-align:right;font-weight:700;color:{{ $rating !== null && $rating <= 5 ? '#c62828' : ($rating !== null && $rating <= 7 ? '#b45309' : '#138a5b') }}">{{ $rating === null ? 'Not provided' : $rating . ' / 10' }}</td></tr>
<tr><td style="padding:9px 0;border-bottom:1px solid #e8edf3;color:#60708a">Action taken by</td><td style="padding:9px 0;border-bottom:1px solid #e8edf3;text-align:right">{{ $admin->name }}</td></tr>
<tr><td style="padding:9px 0;color:#60708a">Updated</td><td style="padding:9px 0;text-align:right">{{ $action->created_at->format('M d, Y h:i A T') }}</td></tr>
</table>
<div style="margin-top:18px;padding:15px;background:{{ $concern ? '#fff7f6' : '#f5f8fc' }};border:1px solid {{ $concern ? '#f5c5c2' : '#e3eaf2' }};border-radius:7px"><div style="font-size:12px;font-weight:700;color:#60708a;text-transform:uppercase">Customer comment</div><div style="font-size:15px;line-height:1.55;margin-top:7px;white-space:pre-wrap">{{ $feedback->firstComment() ?: 'No customer comment was provided.' }}</div></div>
<div style="margin-top:12px;padding:15px;background:#f5f8fc;border-radius:7px"><div style="font-size:12px;font-weight:700;color:#60708a;text-transform:uppercase">Admin action note</div><div style="font-size:14px;line-height:1.55;margin-top:7px;white-space:pre-wrap">{{ $action->note ?: 'No action note was added.' }}</div></div>
<p style="margin:22px 0 8px"><a href="{{ route('feedback.detail', $feedback) }}" style="display:inline-block;background:#e8750b;color:#fff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:6px">Open Feedback Record</a></p><p style="font-size:12px;color:#60708a;line-height:1.5;margin:12px 0 0">Sign in to CFS to view the full response and action history. This link requires administrator access.</p>
</td></tr><tr><td style="padding:15px 26px;background:#f8fafc;border-top:1px solid #e8edf3;color:#60708a;font-size:12px">CFS · Your Feedback. A Better Experience.</td></tr>
</table></td></tr></table>
</body></html>
