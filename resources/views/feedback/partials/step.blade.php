    @php
        $index = array_search($step, array_keys($steps)) + 1;
        $kind = ['visit' => '', 'order' => 'order', 'service' => 'staff', 'food' => 'food', 'final' => 'final'][$step] ?? 'final';
        $titles = ['visit' => 'Date of Visit & Time of Order', 'order' => 'Order Details', 'service' => 'Rate the Store and Staff', 'food' => 'Rate the Food', 'final' => 'Final Questions'];
        $leads = ['visit' => 'Let us know when you visited and when you placed your order.', 'order' => 'Tell us how you received your order and what items you ordered.', 'service' => 'Use the sliders to rate the store environment, speed of service, and staff assistance.', 'food' => 'Slide each line from 1 to 10 based on your food experience today.', 'final' => 'Tell us if your order was accurate, if you were satisfied, and if anything needs attention.'];
        $actions = ['visit' => 'feedback.visit', 'order' => 'feedback.order.save', 'service' => 'feedback.store-rating.save', 'food' => 'feedback.food-rating.save', 'final' => 'feedback.final.save'];
        $backs = ['order' => 'feedback.show', 'service' => 'feedback.order', 'food' => 'feedback.store-rating', 'final' => 'feedback.food-rating'];
        $hasExtra = collect($questions)->contains(fn ($question) => ! $question['core']);
        $hasRatings = collect($questions)->contains(fn ($question) => $question['type'] === 'rating');
        $custom = ! isset(\App\Services\SurveyDefinition::STEPS[$step]);
        $title = $titles[$step] ?? $steps[$step]['title'];
        $lead = $leads[$step] ?? $steps[$step]['description'];
        $stepKeys = array_keys($steps);
        $previousStep = $stepKeys[$index - 2] ?? null;
        $back = ($custom || $step === 'final') && $previousStep
            ? app(\App\Services\SurveyDefinition::class)->url($previousStep)
            : (isset($backs[$step]) ? route($backs[$step]) : route('home'));
    @endphp
    <section class="survey-shell {{ $step === 'visit' ? 'visit-survey-shell' : '' }} {{ $kind ? $kind . '-survey-shell' : '' }} {{ $custom ? 'additional-survey-shell' : '' }}">
        @include('feedback.partials.steps', ['activeStep' => $step])
        <section class="survey-card {{ $kind ? 'no-visual ' . $kind . '-survey-card' : '' }} {{ $hasExtra ? 'has-extra' : '' }} {{ $custom ? 'additional-survey-card' : '' }}">
            <div class="survey-content">
                <span class="survey-pill">Step {{ $index }} of {{ count($steps) }}</span>
                <div class="survey-title-row"><h1>{{ $title }}</h1>
                    @if ($step !== 'order')<svg class="survey-title-icon" viewBox="0 0 24 24" aria-hidden="true">
                        @if ($step === 'service')<path d="M4 21V9l8-5 8 5v12M9 21v-7h6v7M8 10h.01M12 10h.01M16 10h.01"></path>
                        @elseif ($step === 'food')<path d="M4 3v8a4 4 0 0 0 4 4h1v6M4 7h6M10 3v18M16 3v18M16 8h4a2 2 0 0 0 0-4h-4"></path>
                        @elseif ($step === 'final' || $custom)<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"></path>
                        @else<path d="M7 3v4M17 3v4M4 9h16M5 5h14v16H5Z"></path>@endif
                    </svg>@endif
                </div>
                <p class="survey-lead">{{ $lead }}</p>
                <form class="survey-form {{ $step === 'visit' ? 'visit-form' : '' }}" action="{{ $custom ? route('feedback.additional.save', ['stepKey' => $step]) : route($actions[$step]) }}" method="POST" data-survey-form>
                    @csrf
                    <div class="{{ in_array($step, ['service', 'food']) ? 'service-rating-list ' . ($step === 'food' ? 'food-rating-list' : '') : ($step === 'visit' ? 'survey-form-row' : ($custom && $hasRatings ? 'service-rating-list food-rating-list additional-rating-list' : 'final-question-list')) }}">
                        @foreach ($questions as $question)
                            @include('feedback.partials.question', ['question' => $question])
                        @endforeach
                    </div>
                    <div class="survey-actions">
                        @if ($index > 1)
                            <a class="survey-back-link" href="{{ $back }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"></path></svg>Back</a>
                        @else
        <a class="survey-back-link" href="{{ route('home') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"></path></svg>Back</a>
                        @endif
                        <button class="button survey-button" type="submit">{{ $index === count($steps) ? 'Review Answers' : 'Next' }}<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"></path></svg></button>
                    </div>
                </form>
            </div>
            @if ($step === 'visit')<div class="survey-visual" aria-hidden="true"></div>@endif
        </section>
    </section>
