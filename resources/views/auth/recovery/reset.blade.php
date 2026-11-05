<h1 id="recovery-title" tabindex="-1" autofocus>Set New Password</h1>
<p class="muted">Create a new password for your account.</p>
@if (! $email)<div class="alert error" role="alert" aria-live="assertive" data-flash-toast>This reset link is incomplete. Please request a new link.</div>@endif
<form action="{{ route('password.update') }}" method="POST" data-password-form data-pending-label="Saving…">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input id="email" type="hidden" name="email" value="{{ $email }}">
    <div class="recovery-fields">
        @foreach (['password' => 'New Password', 'password_confirmation' => 'Confirm Password'] as $field => $label)
            <div class="field">
                <label for="{{ $field }}">{{ $label }}</label>
                <div class="input-shell">
                    <x-icon name="lock-keyhole" class="input-icon" />
                    <input id="{{ $field }}" name="{{ $field }}" type="password" minlength="8" placeholder="{{ $field === 'password' ? 'New password' : 'Confirm password' }}" autocomplete="new-password" required>
                    <button class="icon-button" type="button" aria-label="Show {{ strtolower($label) }}" aria-controls="{{ $field }}" aria-pressed="false" data-password-toggle title="Show {{ strtolower($label) }}"><x-icon name="eye" class="input-icon eye-open" /><x-icon name="eye-off" class="input-icon eye-closed" /></button>
                </div>
                @if ($field === 'password')<small class="muted">At least 8 characters.</small>@endif
            </div>
        @endforeach
    </div>
    <div class="login-actions">
        <a class="button secondary" href="{{ route('login') }}">Cancel</a>
        <button class="button" type="submit">Save Password</button>
    </div>
    @if ($errors->has('email') || ! $email)
        <a class="recovery-new-link" href="{{ route('password.request') }}">Request a new reset link</a>
    @endif
</form>
