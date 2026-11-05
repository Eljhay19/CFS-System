@if ($feedback->severityLevel() === 'High')
ACTION REQUIRED: CUSTOMER COMPLAINT
{{ $feedback->business->business_name }} received a customer complaint that needs review.
@elseif ($feedback->severityLevel() === 'Medium')
REVIEW RECOMMENDED: CUSTOMER FEEDBACK
{{ $feedback->business->business_name }} received a rating that may need follow-up.
@else
New Customer Feedback Received
A customer response was submitted for {{ $feedback->business->business_name }}.
@endif

Feedback ID: {{ $feedback->displayCode() }}
Customer: {{ $feedback->customerLabel() }}
Submitted: {{ $feedback->submitted_at->format('M d, Y h:i A T') }}
Overall rating: {{ $feedback->ratingAverage() === null ? 'N/A' : $feedback->ratingAverage() . '/10' }}
@if ($feedback->severityLevel() === 'High')
ALERT LEVEL: HIGH — this feedback includes a low rating or a reported problem and needs admin review.
@elseif ($feedback->severityLevel() === 'Medium')
ALERT LEVEL: REVIEW — the rating may need follow-up.
@else
ALERT LEVEL: GENERAL FEEDBACK
@endif
Concern level: {{ $feedback->severityLevel() }}
Status: {{ $feedback->status }}

Rating details:
@php($ratingAnswers = $feedback->answers->filter(fn ($answer) => $answer->type() === \App\Models\Question::TYPE_RATING && is_numeric($answer->answer)))
@forelse ($ratingAnswers as $answer)
- {{ $answer->text() }}: {{ $answer->answer }}/10
@empty
- No individual rating answers were provided.
@endforelse

Customer comment:
{{ $feedback->firstComment() ?: 'No comment was provided.' }}

View Feedback (sign-in required):
{{ route('feedback.detail', $feedback) }}
