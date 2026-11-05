@if ($surveySteps->count())
    <section class="additional-steps" aria-label="Additional survey steps">
        <h2>Additional Steps</h2>
        @foreach ($surveySteps as $surveyStep)
            @php $activeCount = $surveyStep->questions()->whereNull('archived_at')->count(); @endphp
            <div class="additional-step-row" data-step-row="{{ $surveyStep->key }}">
                <div><strong>{{ $surveyStep->title }}</strong><small>{{ $activeCount }} {{ $activeCount === 1 ? 'question' : 'questions' }} &middot; {{ $surveyStep->archived_at ? 'Archived' : ($surveyStep->is_published && $activeCount ? 'Published' : 'Draft') }}</small></div>
                <div class="step-row-actions">
                    @unless ($surveyStep->archived_at)
                        <button class="button compact-button step-action" type="button" data-step-edit="{{ $surveyStep->id }}">Edit Step</button>
                        <form method="POST" action="{{ route('survey-steps.publish', $surveyStep) }}">@csrf<button class="button compact-button step-action" type="submit" {{ !$surveyStep->is_published && !$activeCount ? 'disabled' : '' }}>{{ $surveyStep->is_published ? 'Unpublish' : 'Publish' }}</button></form>
                    @endunless
                    <form method="POST" action="{{ route('survey-steps.archive', $surveyStep) }}">@csrf<button class="button compact-button step-action" type="submit">{{ $surveyStep->archived_at ? 'Restore Step' : 'Archive Step' }}</button></form>
                </div>
            </div>
        @endforeach
    </section>
@endif
@php
    $stepPayload = $surveySteps->mapWithKeys(fn ($item) => [$item->id => ['id' => $item->id, 'title' => $item->title, 'description' => $item->description, 'action' => route('survey-steps.update', $item)]]);
@endphp
<script type="application/json" id="survey-steps-data">@json($stepPayload)</script>
<dialog class="step-dialog" aria-labelledby="step-dialog-heading" data-step-dialog data-error-mode="{{ $errors->any() ? old('_step_mode') : '' }}" data-error-id="{{ old('_step_id') }}">
    <button class="terms-close" type="button" aria-label="Close step form" data-step-close>&times;</button>
    <h2 id="step-dialog-heading" data-step-heading>Add Survey Step</h2>
    <form action="{{ route('survey-steps.store') }}" method="POST" data-step-form data-create-url="{{ route('survey-steps.store') }}">
        @csrf
        <input type="hidden" name="_method" disabled data-step-method>
        <input type="hidden" name="_step_mode" value="add" data-step-mode>
        <input type="hidden" name="_step_id" value="" data-step-id>
        <label for="step-title">Step Title</label><input id="step-title" name="title" maxlength="100" value="{{ old('title') }}" required>
        @error('title')<small class="business-error">{{ $message }}</small>@enderror
        <label for="step-description">Description <span>(optional)</span></label><textarea id="step-description" name="description" maxlength="300">{{ old('description') }}</textarea>
        @error('description')<small class="business-error">{{ $message }}</small>@enderror
        <div class="step-form-actions"><button class="button compact-button step-action" type="button" data-step-close>Cancel</button><button class="button compact-button" type="submit">Save Step</button></div>
    </form>
</dialog>
<script src="{{ asset('js/survey-steps.js') }}" defer></script>
