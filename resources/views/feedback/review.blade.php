@extends('layouts.app')
@section('title', 'Review Answers')
@section('body_class', 'feedback-screen')
@section('content')
    <section class="survey-shell final-survey-shell">
        @include('feedback.partials.steps', ['activeStep' => array_key_last($steps)])
        <section class="survey-card no-visual final-survey-card has-extra review-card">
            <div class="survey-content">
                <span class="survey-pill">Before You Submit</span><div class="survey-title-row"><h1>Review Answers</h1></div>
                <form class="survey-form" action="{{ route('feedback.store') }}" method="POST" data-survey-form data-server-input="{{ session()->hasOldInput() ? '1' : '0' }}">
                    @csrf
                    @if ($errors->has('submission'))
                        <div class="survey-error-message review-submit-alert" role="alert" aria-live="assertive">
                            <x-icon name="triangle-alert" />
                            <div>
                                <strong>We couldn’t submit your feedback yet.</strong>
                                <span>Your answers are still here. You can update the follow-up email below, confirm consent again if you change it, then retry.</span>
                            </div>
                        </div>
                    @endif
                    @if ($errors->has('review'))
                        <div class="survey-error-message review-submit-alert" role="alert" aria-live="assertive">
                            <x-icon name="triangle-alert" />
                            <div>
                                <strong>Please review your answers again.</strong>
                                <span>{{ $errors->first('review') }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="review-list" tabindex="0" aria-label="Answers to review">
                    @foreach ($steps as $step => $details)
                        <div class="review-section">
                            <div class="review-heading"><strong>{{ $details['title'] }}</strong><a href="{{ $definition->url($step) }}">Edit</a></div>
                            @foreach (collect($questions)->where('step', $step) as $question)
                                @php $value = $definition->value($question, session('survey')); @endphp
                                <div class="review-answer"><span>{{ $question['text'] }}</span><strong>{{ $value === 'Like Up' ? 'Yes' : ($value === 'Like Down' ? 'No' : ($value !== '' && $value !== null ? $value : 'Not provided')) }}{{ $question['type'] === 'rating' && is_numeric($value) ? '/10' : '' }}</strong></div>
                            @endforeach
                        </div>
                    @endforeach
                    @include('feedback.partials.follow-up')
                    </div>
            <div class="survey-actions">
                <a class="survey-back-link" href="{{ $definition->url(array_key_last($steps)) }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"></path></svg>Back</a>
                <button class="button survey-button" type="submit">{{ $errors->has('submission') ? 'Retry Submission' : 'Submit Feedback' }}<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"></path></svg></button>
            </div>
                </form>
            </div>
        </section>
    </section>
@endsection
