There is an update on your CFS feedback

The team at {{ $feedback->business->business_name }} reviewed your feedback.
Concern level: {{ $feedback->severityLevel() }}

Feedback ID: {{ $feedback->displayCode() }}
Current status: {{ $action->status }}

Update from the business:
{{ $customerFacingSummary }}

You are receiving this email because you requested follow-up when you submitted your feedback.
