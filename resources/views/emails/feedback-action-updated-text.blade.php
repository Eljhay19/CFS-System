@php($business = $feedback->business)
CFS {{ $feedback->hasCustomerConcern() ? 'Complaint' : 'Feedback' }} Update

Feedback: {{ $feedback->displayCode() }}
Business: {{ $business->business_name }}
Status: {{ $action->previous_status }} -> {{ $action->status }}
Rating: {{ $feedback->ratingAverage() === null ? 'Not provided' : $feedback->ratingAverage() . '/10' }}
Action taken by: {{ $admin->name }}
Updated: {{ $action->created_at->format('M d, Y h:i A T') }}

Customer comment:
{{ $feedback->firstComment() ?: 'No customer comment was provided.' }}

Admin action note:
{{ $action->note ?: 'No action note was added.' }}

Open feedback record: {{ route('feedback.detail', $feedback) }}

Sign in to CFS to view the full response and action history.
