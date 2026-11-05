@php
    $otherAnswers = $feedback->answers->reject(fn ($answer) => $visitAnswers->contains('id', $answer->id) || $answer->type() === \App\Models\Question::TYPE_RATING);
@endphp
<section class="feedback-customer" aria-labelledby="customer-feedback-heading">
    <h2 id="customer-feedback-heading">Customer Feedback</h2>
    <section class="feedback-answer-section feedback-follow-up" aria-labelledby="customer-follow-up-heading">
        <h3 id="customer-follow-up-heading">Customer Follow-up</h3>
        @if ($feedback->hasFollowUp())
            <a class="feedback-follow-up-email" href="mailto:{{ str_replace('%40', '@', rawurlencode($feedback->follow_up_email)) }}"><x-icon name="mail" />{{ $feedback->follow_up_email }}</a>
            <p class="feedback-follow-up-consent">Email follow-up consent given {{ $feedback->follow_up_consent_at->format('M d, Y h:i A') }}</p>
        @else
            <p class="feedback-follow-up-empty">No follow-up requested.</p>
        @endif
    </section>
    @if ($visitAnswers->isNotEmpty())
        <section class="feedback-answer-section">
            <h3>Visit Details</h3>
            <dl class="feedback-answer-grid feedback-visit-details">
                @foreach ($visitAnswers as $answer)
                    <div><dt>{{ $answer->text() }}</dt><dd>{{ $answer->displayValue() }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif
    @if ($ratingQuestions->isNotEmpty())
        <section class="feedback-answer-section">
            <h3>Ratings</h3>
            <dl class="feedback-answer-grid feedback-rating-answers">
                @foreach ($ratingQuestions as $answer)
                    <div><dt>{{ $answer->text() }}</dt><dd>{{ $answer->displayValue() }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif
    @if ($otherAnswers->isNotEmpty())
        <section class="feedback-answer-section">
            <h3>Answers &amp; Comments</h3>
            <dl class="feedback-answer-grid">
                @foreach ($otherAnswers as $answer)
                    <div class="{{ $answer->type() === \App\Models\Question::TYPE_COMMENT ? 'feedback-written-answer' : '' }}"><dt>{{ $answer->text() }}</dt><dd>{{ $answer->displayValue() }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif
    @if ($feedback->answers->isEmpty())<p class="muted">No answers are available for this record.</p>@endif
</section>
