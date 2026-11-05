<section class="survey-follow-up" aria-labelledby="follow-up-heading" data-follow-up>
    <h2 id="follow-up-heading">Optional Follow-up</h2>
    <label class="survey-follow-up-choice" for="follow_up_requested">
        <input id="follow_up_requested" name="follow_up_requested" type="checkbox" value="1" {{ old('follow_up_requested') == '1' ? 'checked' : '' }} aria-controls="follow-up-fields" aria-expanded="{{ old('follow_up_requested') == '1' ? 'true' : 'false' }}">
        <span>I would like to receive a follow-up about my feedback.</span>
    </label>
    @error('follow_up_requested')<p class="survey-follow-up-error" role="alert">{{ $message }}</p>@enderror
    <div id="follow-up-fields" class="survey-follow-up-fields" data-follow-up-fields>
        <label for="follow_up_email">Email Address <span class="required-dot">*</span></label>
        <div class="survey-follow-up-input"><x-icon name="mail" /><input id="follow_up_email" name="follow_up_email" type="email" autocomplete="email" inputmode="email" maxlength="254" placeholder="Your email address" value="{{ old('follow_up_requested') == '1' ? old('follow_up_email') : '' }}" aria-describedby="follow-up-privacy{{ $errors->has('follow_up_email') ? ' follow-up-email-error' : '' }}" @if($errors->has('follow_up_email')) aria-invalid="true" @endif></div>
        @error('follow_up_email')<p id="follow-up-email-error" class="survey-follow-up-error" role="alert">{{ $message }}</p>@enderror
        <label class="survey-follow-up-choice" for="follow_up_consent">
            <input id="follow_up_consent" name="follow_up_consent" type="checkbox" value="1" {{ old('follow_up_requested') == '1' && old('follow_up_consent') == '1' ? 'checked' : '' }} @if($errors->has('follow_up_consent')) aria-describedby="follow-up-consent-error" aria-invalid="true" @endif>
            <span>I consent to the use of my email for follow-up about this feedback.</span>
        </label>
        @error('follow_up_consent')<p id="follow-up-consent-error" class="survey-follow-up-error" role="alert">{{ $message }}</p>@enderror
        <p id="follow-up-privacy">Use the email where you want the business to contact you. You can change it before submitting; if you edit it, confirm consent again. Only the authorized admin can see it, and a response with an email is not anonymous.</p>
    </div>
</section>
