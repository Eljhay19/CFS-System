    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Customer Feedback and Satisfaction Survey</p>
            <h1>Your Feedback Helps Us <span class="accent">Do Better.</span></h1>
            <p class="hero-lead">
                Share your thoughts and help us improve our service.
            </p>
            @if ($hasDraft)
                <p class="workflow-note">Your unfinished response is available for {{ config('feedback.draft_minutes') }} minutes of inactivity.</p>
                <div class="draft-actions"><a class="button" href="{{ route('feedback.continue') }}">Continue Survey</a><form action="{{ route('feedback.restart') }}" method="POST">@csrf<button class="button secondary" type="submit">Start Over</button></form></div>
            @elseif (! $business || ! $business->survey_enabled)
                <p class="workflow-note closed-survey-message">{{ $business ? $business->closedMessage() : 'This survey is currently closed.' }}</p>
            @else
            <form class="hero-form" action="{{ route('feedback.start') }}" method="POST" data-terms-confirmed="{{ $termsConfirmed ? '1' : '0' }}" data-terms-url="{{ route('terms', ['from' => 'home']) }}">
                @csrf
                <label class="rating-choice consent-option" data-home-agree-option>
                    <input type="radio" name="consent" value="agree" required {{ $termsConfirmed ? 'checked' : '' }}>
                    <span>I agree with the <a class="inline-policy-link" href="{{ route('terms', ['from' => 'home']) }}">Terms &amp; Conditions</a></span>
                </label>
                <label class="rating-choice consent-option last">
                    <input type="radio" name="consent" value="decline" required>
                    <span>No thanks. I do not wish to continue.</span>
                </label>
                <button class="button hero-cta" type="submit" {{ $termsConfirmed ? '' : 'disabled' }} data-start-survey>
                    Let's Begin
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                </button>
            </form>
            @endif
        </div>
        <div class="hero-visual" role="img" aria-label="Modern service counter">
            <div class="hero-feedback-card">
                <h2 class="hero-card-title">We'd love to hear from you!</h2>
                <div class="mock-line tiny"></div>
                <div class="face-row" aria-hidden="true">
                    <span class="face">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 9.5h.01M15.5 9.5h.01M8.5 16c1-1 2.2-1.5 3.5-1.5s2.5.5 3.5 1.5"></path></svg>
                    </span>
                    <span class="face">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 9.5h.01M15.5 9.5h.01M9 15.5c.9-.7 1.9-1 3-1s2.1.3 3 1"></path></svg>
                    </span>
                    <span class="face">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 9.5h.01M15.5 9.5h.01M8.5 15h7"></path></svg>
                    </span>
                    <span class="face">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 9.5h.01M15.5 9.5h.01M8.5 14.5c1 .8 2.2 1.2 3.5 1.2s2.5-.4 3.5-1.2"></path></svg>
                    </span>
                    <span class="face active">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 9.5h.01M15.5 9.5h.01M8 14.5c1.1 1.6 2.4 2.4 4 2.4s2.9-.8 4-2.4"></path></svg>
                    </span>
                </div>
                <div class="mock-line orange"></div>
                <div class="mock-line long"></div>
                <div class="mock-line short"></div>
            </div>
        </div>
    </section>

    <script src="{{ asset('js/home-consent.js') }}" defer></script>

    <div class="feature-row">
        <div class="feature">
            <svg class="feature-icon" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5 22c.9-5 3.2-7.5 7-7.5s6.1 2.5 7 7.5"/></svg>
            <div>
                <strong>No Sign-Up Needed</strong>
                <p class="muted feature-text">Submit feedback without creating an account.</p>
            </div>
        </div>
        <div class="feature">
            <svg class="feature-icon" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></svg>
            <div>
                <strong>Step-by-Step Survey</strong>
                <p class="muted feature-text">Answer clear questions in a guided flow.</p>
            </div>
        </div>
        <div class="feature">
            <svg class="feature-icon" viewBox="0 0 24 24"><path d="M5 20V12M12 20V5M19 20v-9"/></svg>
            <div>
                <strong>Ratings & Comments</strong>
                <p class="muted feature-text">Share ratings, concerns, and suggestions.</p>
            </div>
        </div>
    </div>
