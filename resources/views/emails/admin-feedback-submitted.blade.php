<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $feedback->severityLevel() === 'High' ? 'Customer complaint needs attention' : ($feedback->severityLevel() === 'Medium' ? 'Customer feedback review recommended' : 'New customer feedback') }}</title>
</head>
<body style="margin:0;background:#f3f6fa;color:#10213f;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6fa;padding:24px 12px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #dce4ee;border-radius:12px;overflow:hidden;">
                <tr><td style="background:#10213f;padding:20px 24px;color:#ffffff;font-size:20px;font-weight:700;">CFS Customer Feedback</td></tr>
                @if ($feedback->severityLevel() === 'High')
                    <tr><td style="background:#fff1f0;border-bottom:1px solid #fecaca;padding:16px 24px;color:#b42318;">
                        <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Action required</div>
                        <div style="font-size:20px;font-weight:700;margin-top:5px;">Customer complaint needs attention</div>
                        <div style="font-size:14px;margin-top:5px;">A low rating or reported problem was found. Please review the customer’s comment and rating details below.</div>
                    </td></tr>
                @elseif ($feedback->severityLevel() === 'Medium')
                    <tr><td style="background:#fff7ed;border-bottom:1px solid #fed7aa;padding:16px 24px;color:#9a4d00;">
                        <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Review recommended</div>
                        <div style="font-size:20px;font-weight:700;margin-top:5px;">Customer feedback may need follow-up</div>
                        <div style="font-size:14px;margin-top:5px;">The rating indicates a service issue may need review. Check the response details below.</div>
                    </td></tr>
                @else
                    <tr><td style="padding:20px 24px 4px;font-size:20px;font-weight:700;">New customer feedback received</td></tr>
                @endif
                <tr><td style="padding:16px 24px 24px;">
                    <p style="margin:0 0 16px;color:#455875;">A customer response was submitted for <strong>{{ $feedback->business->business_name }}</strong>.</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                        <tr><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;color:#60708a;">Feedback ID</td><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;text-align:right;font-weight:700;">{{ $feedback->displayCode() }}</td></tr>
                        <tr><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;color:#60708a;">Customer</td><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;text-align:right;">{{ $feedback->customerLabel() }}</td></tr>
                        <tr><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;color:#60708a;">Submitted</td><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;text-align:right;">{{ $feedback->submitted_at->format('M d, Y h:i A T') }}</td></tr>
                        @php($overallRating = $feedback->ratingAverage())
                        <tr><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;color:#60708a;">Concern level</td><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;text-align:right;font-weight:700;color:{{ $feedback->severityLevel() === 'High' ? '#b42318' : ($feedback->severityLevel() === 'Medium' ? '#b45309' : '#138a5b') }};">{{ $feedback->severityLevel() }}</td></tr>
                        <tr><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;color:#60708a;">Overall rating</td><td style="padding:9px 0;border-bottom:1px solid #e6ebf2;text-align:right;font-weight:700;color:{{ $feedback->severityLevel() === 'High' ? '#b42318' : (($overallRating !== null && $overallRating <= 7) ? '#b45309' : '#138a5b') }};">{{ $overallRating === null ? 'N/A' : $overallRating . '/10' }}{{ $feedback->hasCustomerConcern() ? ' · Needs attention' : '' }}</td></tr>
                        <tr><td style="padding:9px 0;color:#60708a;">Status</td><td style="padding:9px 0;text-align:right;font-weight:700;{{ $feedback->hasCustomerConcern() ? 'color:#b42318;' : '' }}">{{ $feedback->status }}</td></tr>
                    </table>
                    @php($ratingAnswers = $feedback->answers->filter(fn ($answer) => $answer->type() === \App\Models\Question::TYPE_RATING && is_numeric($answer->answer)))
                    @if ($ratingAnswers->isNotEmpty())
                        <div style="margin-top:18px;font-size:12px;font-weight:700;color:#60708a;text-transform:uppercase;">Rating details</div>
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:6px;border-collapse:collapse;font-size:13px;">
                            @foreach ($ratingAnswers as $answer)
                                @php($score = (float) $answer->answer)
                                <tr><td style="padding:8px 0;border-bottom:1px solid #e6ebf2;color:#455875;">{{ $answer->text() }}</td><td style="padding:8px 0;border-bottom:1px solid #e6ebf2;text-align:right;font-weight:700;color:{{ $score <= 5 ? '#b42318' : ($score <= 7 ? '#b45309' : '#138a5b') }};">{{ $score }}/10</td></tr>
                            @endforeach
                        </table>
                    @endif
                    <div style="margin-top:18px;padding:14px 16px;background:{{ $feedback->hasCustomerConcern() ? '#fff1f0' : '#f5f8fc' }};border-left:4px solid {{ $feedback->hasCustomerConcern() ? '#d92d20' : '#1f5fae' }};border-radius:4px;">
                        <div style="font-size:12px;font-weight:700;color:#60708a;text-transform:uppercase;">Customer comment</div>
                        <div style="font-size:15px;line-height:1.55;margin-top:6px;white-space:pre-wrap;">{{ $feedback->firstComment() ?: 'No comment was provided.' }}</div>
                    </div>
                    <p style="margin:22px 0 0;">
                        <a href="{{ route('feedback.detail', $feedback) }}" style="display:inline-block;background:#e8750b;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:6px;">View Feedback</a>
                    </p>
                    <p style="margin:16px 0 0;color:#60708a;font-size:12px;line-height:1.5;">Sign in to the CFS admin dashboard to view the full response. This link does not grant public access.</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
