@php $progressSteps = $steps ?? app(\App\Services\SurveyDefinition::class)->steps(); @endphp
<aside class="survey-steps {{ $activeStep === 'order' ? 'order-survey-steps' : '' }} {{ count($progressSteps) > 5 ? 'has-additional-steps' : '' }}" aria-label="Survey progress">
    @foreach ($progressSteps as $key => $details)
        <div class="survey-step {{ $key === $activeStep ? 'active' : '' }}"><span class="survey-step-number" aria-label="Step {{ $loop->iteration }}">{{ $loop->iteration }}</span><span><strong>{{ $details['title'] }}</strong><span>{{ $details['description'] }}</span></span></div>
    @endforeach
</aside>
